<?php declare(strict_types=1);

namespace Labrador\Web\Router;

use Amp\Http\Server\Middleware;
use Amp\Http\Server\Request;
use Amp\Http\Server\RequestHandler;
use Labrador\Web\Router\Mapping\RequestMapping;
use Psr\Log\LoggerInterface;

final class LoggingRouter implements Router {

    public function __construct(
        private readonly Router $router,
        private readonly LoggerInterface $logger
    ) {
    }

    public function addRoute(RequestMapping $requestMapping, RequestHandler $requestHandler, Middleware ...$middlewares) : Route {
        $message = 'Routing "{method} {path}" to {request_handler}';
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
                'request_handler' => $requestHandler::class,
                'middleware' => $middlewareClasses
            ]
        );
        return $this->router->addRoute($requestMapping, $requestHandler, ...$middlewares);
    }

    public function match(Request $request) : RoutingResolution {
        $resolution = $this->router->match($request);

        if ($resolution->reason === RoutingResolutionReason::NotFound || $resolution->reason === RoutingResolutionReason::MethodNotAllowed) {
            $message = $resolution->reason === RoutingResolutionReason::NotFound ? 'no route was found' : 'route does not allow requested method';
            $this->logger->notice(
                'Failed routing "{method} {path}" to a request handler because ' . $message .'.',
                [
                    'method' => $request->getMethod(),
                    'path' => $request->getUri()->getPath()
                ]
            );
        } else {
            $this->logger->info(
                'Routed "{method} {path}" to {request_handler}.',
                [
                    'method' => $request->getMethod(),
                    'path' => $request->getUri()->getPath(),
                    'request_handler' => $resolution->requestHandler::class
                ]
            );
        }
        return $resolution;
    }

    public function getRoutes() : array {
        return $this->router->getRoutes();
    }
}
