<?php

declare(strict_types=1);

/**
 * A router that is a wrapper around the FastRoute library that adheres to
 * Labrador\Router\Router interface.
 *
 * @license See LICENSE in source root
 *
 * @see https://github.com/nikic/FastRoute
 */

namespace Cspray\Labrador\Http\Router;

use Amp\Http\Server\Request;
use Amp\Http\Server\Response;
use Amp\Promise;
use Amp\Success;
use Cspray\Labrador\Http\Controller\Controller;
use Cspray\Labrador\Http\Exception\InvalidHandlerException;
use Cspray\Labrador\Http\Exception\InvalidTypeException;
use Cspray\Labrador\Http\StatusCodes;
use FastRoute\Dispatcher;
use FastRoute\RouteCollector;

class FastRouteRouter implements Router {

    private $dispatcherCb;
    private $collector;
    private $routes = [];
    private $notFoundController;
    private $methodNotAllowedController;
    private $mountedPrefix = [];

    /**
     * Pass a HandlerResolver, a FastRoute\RouteCollector and a callback that
     * returns a FastRoute\Dispatcher.
     *
     * We ask for a callback instead of the object itself to work around needing
     * the list of routes at FastRoute dispatcher instantiation. The $dispatcherCb is
     * invoked when Router::match is called and it should expect an array of data
     * in the same format as $collector->getData().
     *
     * @param RouteCollector $collector
     * @param callable $dispatcherCb
     */
    public function __construct(RouteCollector $collector, callable $dispatcherCb) {
        $this->collector = $collector;
        $this->dispatcherCb = $dispatcherCb;

    }

    /**
     * @param string $pattern
     * @param Controller $controller
     * @return $this
     */
    public function get(string $pattern, Controller $controller) : self {
        $this->addRoute('GET', $pattern, $controller);
        return $this;
    }

    /**
     * @param string $pattern
     * @param Controller $controller
     * @return $this
     */
    public function post(string $pattern, Controller $controller) : self {
        $this->addRoute('POST', $pattern, $controller);
        return $this;
    }

    /**
     * @param string $pattern
     * @param Controller $controller
     * @return $this
     */
    public function put(string $pattern, Controller $controller) : self {
        $this->addRoute('PUT', $pattern, $controller);
        return $this;
    }

    /**
     * @param string $pattern
     * @param Controller $controller
     * @return $this
     */
    public function delete(string $pattern, Controller $controller) : self {
        $this->addRoute('DELETE', $pattern, $controller);
        return $this;
    }

    /**
     * Allows you to easily prefix routes to composer complex URL patterns without
     * constantly retyping pattern matches.
     *
     * @param string $prefix
     * @param callable $cb
     * @return $this
     */
    public function mount(string $prefix, callable $cb) : self {
        $this->mountedPrefix[] = $prefix;
        $cb($this);
        $this->mountedPrefix = [];
        return $this;
    }

    /**
     * @return string
     */
    public function root() : string {
        return $this->isMounted() ? '' : '/';
    }

    /**
     * @return bool
     */
    public function isMounted() : bool {
        return !empty($this->mountedPrefix);
    }

    /**
     * @param string $method
     * @param string $pattern
     * @param Controller $controller
     * @return void
     */
    public function addRoute(string $method, string $pattern, Controller $controller) : void {
        // @todo implement FastRouterRouteCollector and parse required data from Route objects
        if ($this->isMounted()) {
            $pattern = implode('', $this->mountedPrefix) . $pattern;
        }
        $this->routes[] = new Route($pattern, $method, get_class($controller));
        $this->collector->addRoute($method, $pattern, $controller);
    }

    /**
     * @param Request $request
     * @return Controller
     * @throws InvalidTypeException
     */
    public function match(Request $request) : Controller {
        $uri = $request->getUri();
        $path = empty($uri->getPath()) ? '/' : $uri->getPath();
        $route = $this->getDispatcher()->dispatch($request->getMethod(), $path);
        $status = array_shift($route);

        if ($notOkResolved = $this->guardNotOkMatch($status, $route)) {
            return $notOkResolved;
        }

        list($controller, $params) = $route;

        foreach ($params as $k => $v) {
            $request->setAttribute($k, urldecode($v));
        }


        return $controller;
    }

    /**
     * @param integer $status
     * @param array $route
     * @return Controller
     */
    private function guardNotOkMatch(int $status, array $route) : ?Controller {
        if (empty($route) || $status === Dispatcher::NOT_FOUND) {
            return $this->getNotFoundController();
        }

        if ($status === Dispatcher::METHOD_NOT_ALLOWED) {
            return $this->getMethodNotAllowedController();
        }

        return null;
    }

    /**
     * @return Dispatcher
     * @throws InvalidTypeException
     */
    private function getDispatcher() : Dispatcher {
        $cb = $this->dispatcherCb;
        $dispatcher = $cb($this->collector->getData());
        if (!$dispatcher instanceof Dispatcher) {
            $msg = 'A FastRoute\\Dispatcher must be returned from dispatcher callback injected in constructor';
            throw new InvalidTypeException($msg);
        }

        return $dispatcher;
    }

    public function getRoutes() : array {
        return $this->routes;
    }

    /**
     * This function GUARANTEES that a Controller will always be returned, even if a Controller has not previously
     * been set.
     *
     * @return Controller
     */
    public function getNotFoundController() : Controller {
        if (!$this->notFoundController) {
            return new class implements Controller {

                public function beforeAction(): Promise {
                    return new Success();
                }

                public function afterAction(): Promise {
                    return new Success();
                }

                /**
                 * @param Request $request
                 *
                 * @return Promise<\Amp\Http\Server\Response>
                 */
                public function handleRequest(Request $request): Promise {
                    $response = new Response(
                        StatusCodes::NOT_FOUND,
                        [],
                        'Not Found'
                    );
                    return new Success($response);
                }
            };
        }

        return $this->notFoundController;
    }

    /**
     * This function GUARANTEES that a callable will always be returned.
     *
     * @return callable
     */
    public function getMethodNotAllowedController() : Controller {
        if (!$this->methodNotAllowedController) {
            return new class implements Controller {

                public function beforeAction(): Promise {
                    return new Success();
                }

                public function afterAction(): Promise {
                    return new Success();
                }

                /**
                 * @param Request $request
                 *
                 * @return Promise<\Amp\Http\Server\Response>
                 */
                public function handleRequest(Request $request): Promise {
                    $response = new Response(
                        StatusCodes::METHOD_NOT_ALLOWED,
                        [],
                        'Method Not Allowed'
                    );
                    return new Success($response);
                }
            };
        }

        return $this->methodNotAllowedController;
    }

    /**
     * Set the $controller that will be passed to the resolved route when a
     * handler could not be found for a given request.
     *
     * @param Controller $controller
     * @return $this
     */
    public function setNotFoundController(Controller $controller) : self {
        $this->notFoundController = $controller;
        return $this;
    }

    /**
     * Set the controller that will be passed to the resolved route when a handler
     * is found for a given request but the HTTP method is not allowed.
     *
     * @param Controller $controller
     * @return $this
     */
    public function setMethodNotAllowedController(Controller $controller) : self {
        $this->methodNotAllowedController = $controller;
        return $this;
    }

}
