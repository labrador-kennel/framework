<?php declare(strict_types=1);

namespace Cspray\Labrador\Http\Router;

use Cspray\Labrador\Http\Controller\Controller;
use Cspray\Labrador\Http\Controller\MiddlewareController;
use Cspray\Labrador\Http\Exception\InvalidTypeException;

use Amp\Http\Status;
use Amp\Promise;
use Amp\Success;
use Amp\Http\Server\Middleware;
use Amp\Http\Server\Request;
use Amp\Http\Server\Response;
use FastRoute\Dispatcher;
use FastRoute\RouteCollector;

/**
 * A Router implementation that makes use of FastRoute to do the actual heavy lifting.
 */
class FastRouteRouter implements Router {

    private $dispatcherCb;
    private $collector;
    private $routes = [];
    private $notFoundController;
    private $methodNotAllowedController;

    /**
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
     * @param string $method
     * @param string $pattern
     * @param Controller $controller
     * @param Middleware[] $middlewares
     * @return void
     */
    public function addRoute(string $method, string $pattern, Controller $controller, Middleware ...$middlewares) : void {
        $this->routes[] = new Route($pattern, $method, $controller->toString());
        if (!empty($middlewares)) {
            $controller = new MiddlewareController($controller, ...$middlewares);
        }
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
            return $this->defaultController(
                'DefaultNotFoundController',
                Status::NOT_FOUND,
                'Not Found'
            );
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
            return $this->defaultController(
                'DefaultMethodNotAllowedController',
                Status::METHOD_NOT_ALLOWED,
                'Method Not Allowed'
            );
        }

        return $this->methodNotAllowedController;
    }

    private function defaultController(string $controllerDescription, int $status, string $body) : Controller {
        return new class($controllerDescription, $status, $body) implements Controller {

            private $description;
            private $status;
            private $body;

            public function __construct(string $description, int $status, string $body) {
                $this->description = $description;
                $this->status = $status;
                $this->body = $body;
            }

            /**
             * @param Request $request
             *
             * @return Promise<\Amp\Http\Server\Response>
             */
            public function handleRequest(Request $request): Promise {
                return new Success(new Response($this->status, [], $this->body));
            }

            public function toString() : string {
                return $this->description;
            }
        };
    }

    /**
     * Set the $controller that will be passed to the resolved route when a
     * handler could not be found for a given request.
     *
     * @param Controller $controller
     */
    public function setNotFoundController(Controller $controller) : void {
        $this->notFoundController = $controller;
    }

    /**
     * Set the controller that will be passed to the resolved route when a handler
     * is found for a given request but the HTTP method is not allowed.
     *
     * @param Controller $controller
     */
    public function setMethodNotAllowedController(Controller $controller) : void {
        $this->methodNotAllowedController = $controller;
    }
}
