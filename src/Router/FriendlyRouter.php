<?php declare(strict_types=1);

namespace Cspray\Labrador\Http\Router;

use Amp\Http\Server\Middleware;
use Amp\Http\Server\Request;
use Cspray\Labrador\Http\Controller\Controller;

/**
 * A Router that acts as a decorator over other Router implementations and provides convenience methods for defining
 * complex routing.
 */
class FriendlyRouter implements Router {

    private $mounts = [
        'prefix' => [],
        'middleware' => [],
    ];
    private $router;

    public function __construct(Router $router) {
        $this->router = $router;
    }

    /**
     * Define a Route that should respond to a GET request.
     *
     * @param string $pattern
     * @param Controller $controller
     * @param Middleware[] $middlewares
     * @return $this
     */
    public function get(string $pattern, Controller $controller, Middleware ...$middlewares) : self {
        $this->addRoute('GET', $pattern, $controller, ...$middlewares);
        return $this;
    }

    /**
     * Define a Route that should respond to a POST request.
     *
     * @param string $pattern
     * @param Controller $controller
     * @param Middleware[] $middlewares
     * @return $this
     */
    public function post(string $pattern, Controller $controller, Middleware ...$middlewares) : self {
        $this->addRoute('POST', $pattern, $controller, ...$middlewares);
        return $this;
    }

    /**
     * Define a Route that should respond to a PUT request.
     *
     * @param string $pattern
     * @param Controller $controller
     * @param Middleware[] $middlewares
     * @return $this
     */
    public function put(string $pattern, Controller $controller, Middleware ...$middlewares) : self {
        $this->addRoute('PUT', $pattern, $controller, ...$middlewares);
        return $this;
    }

    /**
     * Define a Route that should respond to a DELETE request.
     *
     * @param string $pattern
     * @param Controller $controller
     * @param Middleware[] $middlewares
     * @return $this
     */
    public function delete(string $pattern, Controller $controller, Middleware ...$middlewares) : self {
        $this->addRoute('DELETE', $pattern, $controller, ...$middlewares);
        return $this;
    }

    /**
     * Allows you to easily prefix routes to compose complex URL patterns without constantly retyping the $prefix.
     *
     * You can safely nest this method call, meaning you can call $router->mount() on the Router instance passed to
     * $callback.
     *
     * The $callback method signature should be as follows:
     *
     * function(Router $router) : void
     *
     * @param string $prefix
     * @param callable $callback
     * @param Middleware[] $middlewares
     * @return void
     */
    public function mount(string $prefix, callable $callback, Middleware ...$middlewares) : void {
        $this->mounts['prefix'][] = $prefix;
        $this->mounts['middleware'] = array_merge([], $this->mounts['middleware'], $middlewares);
        $callback($this);
        $this->mounts['prefix'] = [];
        $this->mounts['middleware'] = [];
    }

    /**
     * @return bool
     */
    public function isMounted() : bool {
        return !empty($this->mounts['prefix']);
    }

    /**
     * @param string $method
     * @param string $pattern
     * @param Controller $controller
     * @param Middleware[] $middlewares
     * @return void
     */
    public function addRoute(
        string $method,
        string $pattern,
        Controller $controller,
        Middleware ...$middlewares
    ) : void {
        // @todo implement FastRouterRouteCollector and parse required data from Route objects
        if ($this->isMounted()) {
            $pattern = implode('', $this->mounts['prefix']) . $pattern;
            $middlewares = array_merge([], $this->mounts['middleware'], $middlewares);
        }

        $this->router->addRoute($method, $pattern, $controller, ...$middlewares);
    }

    /**
     * @param Request $request
     * @return Controller
     */
    public function match(Request $request): Controller {
        return $this->router->match($request);
    }

    /**
     * @return Route[]
     */
    public function getRoutes(): array {
        return $this->router->getRoutes();
    }

    public function setNotFoundController(Controller $controller): void {
        $this->router->setNotFoundController($controller);
    }

    public function getNotFoundController(): Controller {
        return $this->router->getNotFoundController();
    }

    public function setMethodNotAllowedController(Controller $controller): void {
        $this->router->setMethodNotAllowedController($controller);
    }

    public function getMethodNotAllowedController(): Controller {
        return $this->router->getMethodNotAllowedController();
    }
}
