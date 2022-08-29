<?php declare(strict_types=1);

namespace Cspray\Labrador\Http\Router;

use Cspray\AnnotatedContainer\Attribute\Service;
use Cspray\Labrador\Http\Controller\Controller;
use Cspray\Labrador\Http\Controller\MiddlewareController;
use Cspray\Labrador\Http\Exception\InvalidType;

use Amp\Http\Server\Middleware;
use Amp\Http\Server\Request;
use FastRoute\Dispatcher;
use FastRoute\RouteCollector;

/**
 * A Router implementation that makes use of FastRoute to do the actual heavy lifting.
 */
#[Service]
final class FastRouteRouter implements Router {

    private $dispatcherCb;
    private readonly RouteCollector $collector;

    /**
     * @var Route[]
     */
    private array $routes = [];

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

    public function addRoute(
        RequestMapping $requestMapping,
        Controller     $controller,
        Middleware ...$middlewares
    ) : Route {
        if (count($middlewares) > 0) {
            $controller = new MiddlewareController($controller, ...$middlewares);
        }
        $route = new Route($requestMapping, $controller);
        $this->routes[] = $route;
        $this->collector->addRoute(
            $requestMapping->method->value,
            $requestMapping->pathPattern,
            $route
        );

        return $route;
    }

    /**
     * @throws InvalidType
     */
    public function match(Request $request) : RoutingResolution {
        $uri = $request->getUri();
        $path = empty($uri->getPath()) ? '/' : $uri->getPath();
        $routeData = $this->getDispatcher()->dispatch($request->getMethod(), $path);
        $status = (int) array_shift($routeData);

        $controller = null;

        if ($notOkResolved = $this->guardNotOkMatch($status, $routeData)) {
            $reason = $notOkResolved;
        } else {
            $reason = RoutingResolutionReason::RequestMatched;
            list($route, $params) = $routeData;

            assert($route instanceof Route);
            assert(is_array($params));

            $controller = $route->controller;
            foreach ($params as $k => $v) {
                assert(is_string($k));
                assert(is_string($v));
                $request->setAttribute($k, urldecode($v));
            }
        }

        return new RoutingResolution($controller, $reason);
    }

    private function guardNotOkMatch(int $status, array $route) : ?RoutingResolutionReason {
        if (empty($route) || $status === Dispatcher::NOT_FOUND) {
            return RoutingResolutionReason::NotFound;
        }

        if ($status === Dispatcher::METHOD_NOT_ALLOWED) {
            return RoutingResolutionReason::MethodNotAllowed;
        }

        return null;
    }

    /**
     * @return Dispatcher
     * @throws InvalidType
     */
    private function getDispatcher() : Dispatcher {
        $cb = $this->dispatcherCb;
        $dispatcher = $cb($this->collector->getData());
        if (!$dispatcher instanceof Dispatcher) {
            throw InvalidType::fromDispatcherCallbackInvalidReturn();
        }

        return $dispatcher;
    }

    public function getRoutes() : array {
        return $this->routes;
    }

}
