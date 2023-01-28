<?php declare(strict_types=1);

namespace Labrador\Http\Router;

use Amp\Http\Server\Middleware;
use Amp\Http\Server\Request;
use Labrador\Http\Controller\Controller;
use Labrador\Http\HttpMethod;

/**
 * A Router that acts as a decorator over other Router implementations and provides convenience methods for defining
 * complex routing.
 */
final class FriendlyRouter implements Router {

    /**
     * @var array{prefix: string[], middleware: Middleware[]}
     */
    private array $mounts = [
        'prefix' => [],
        'middleware' => [],
    ];

    public function __construct(
        private readonly Router $router
    ) {}

    /**
     * Define a Route that should respond to a GET request.
     *
     * @param string $pattern
     * @param Controller $controller
     * @param Middleware[] $middlewares
     * @return $this
     */
    public function get(string $pattern, Controller $controller, Middleware ...$middlewares) : self {
        $this->addRoute(MethodAndPathRequestMapping::fromMethodAndPath(HttpMethod::Get, $pattern), $controller, ...$middlewares);
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
        $this->addRoute(MethodAndPathRequestMapping::fromMethodAndPath(HttpMethod::Post, $pattern), $controller, ...$middlewares);
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
        $this->addRoute(MethodAndPathRequestMapping::fromMethodAndPath(HttpMethod::Put, $pattern), $controller, ...$middlewares);
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
        $this->addRoute(MethodAndPathRequestMapping::fromMethodAndPath(HttpMethod::Delete, $pattern), $controller, ...$middlewares);
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

    public function addRoute(
        RequestMapping $requestMapping,
        Controller                  $controller,
        Middleware ...$middlewares
    ) : Route {
        if ($this->isMounted()) {
            $pattern = implode('', $this->mounts['prefix']) . $requestMapping->getPath();
            $requestMapping = $requestMapping->withPath($pattern);
            $middlewares = array_merge([], $this->mounts['middleware'], $middlewares);
        }

        return $this->router->addRoute($requestMapping, $controller, ...$middlewares);
    }

    public function match(Request $request): RoutingResolution {
        return $this->router->match($request);
    }

    public function getRoutes(): array {
        return $this->router->getRoutes();
    }

}
