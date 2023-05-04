<?php declare(strict_types=1);

namespace Labrador\Http\Router;

use Amp\Http\Server\Middleware;
use Amp\Http\Server\Request;
use Labrador\Http\Controller\Controller;
use Psr\Log\LoggerInterface;

final class LoggingRouter implements Router {

    public function __construct(
        private readonly Router $router,
        private readonly LoggerInterface $logger
    ) {}

    public function addRoute(RequestMapping $requestMapping, Controller $controller, Middleware ...$middlewares) : Route {
        $message = 'Routing "{method} {path}" to {controller}';
        $middlewareClasses = array_map(fn(Middleware $middleware) => $middleware::class, $middlewares);
        if (count($middlewares) > 0) {
            $middlewareDescription = implode(', ', $middlewareClasses);
            $message .= ' with middleware ' . $middlewareDescription;
        }

        $this->logger->info(
            $message . '.',
            [
                'method' => $requestMapping->getHttpMethod()->value,
                'path' => $requestMapping->getPath(),
                'controller' => $controller->toString(),
                'middleware' => $middlewareClasses
            ]
        );
        return $this->router->addRoute($requestMapping, $controller, ...$middlewares);
    }

    public function match(Request $request) : RoutingResolution {
        $resolution = $this->router->match($request);

        if ($resolution->reason === RoutingResolutionReason::NotFound || $resolution->reason === RoutingResolutionReason::MethodNotAllowed) {
            $message = $resolution->reason === RoutingResolutionReason::NotFound ? 'no route was found' : 'route does not allow requested method';
            $this->logger->notice(
                'Failed routing "{method} {path}" to a controller because ' . $message .'.',
                [
                    'method' => $request->getMethod(),
                    'path' => $request->getUri()->getPath()
                ]
            );

        } else {
            $this->logger->info(
                'Routed "{method} {path}" to {controller}.',
                [
                    'method' => $request->getMethod(),
                    'path' => $request->getUri()->getPath(),
                    'controller' => $resolution->controller?->toString()
                ]
            );
        }
        return $resolution;
    }

    public function getRoutes() : array {
        return $this->router->getRoutes();
    }
}