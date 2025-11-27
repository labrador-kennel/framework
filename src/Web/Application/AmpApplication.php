<?php declare(strict_types=1);

namespace Labrador\Web\Application;

use Amp\Http\HttpStatus;
use Amp\Http\Server\ErrorHandler;
use Amp\Http\Server\HttpServer;
use Amp\Http\Server\Middleware;
use Amp\Http\Server\Request;
use Amp\Http\Server\RequestHandler;
use Amp\Http\Server\Response;
use Labrador\AsyncEvent\Emitter;
use Labrador\Web\Application\Analytics\PreciseTime;
use Labrador\Web\Application\Analytics\RequestAnalyticsQueue;
use Labrador\Web\Application\Analytics\RequestBenchmark;
use Labrador\Web\Application\Event\AddGlobalMiddleware;
use Labrador\Web\Application\Event\AddRoutes;
use Labrador\Web\Application\Event\ApplicationStarted;
use Labrador\Web\Application\Event\ApplicationStopped;
use Labrador\Web\Application\Event\ReceivingConnections;
use Labrador\Web\Application\Event\RequestReceived;
use Labrador\Web\Application\Event\ResponseSent;
use Labrador\Web\Application\Event\WillInvokeRequestHandler;
use Labrador\Web\Middleware\GlobalMiddlewareCollection;
use Labrador\Web\RequestAttribute;
use Labrador\Web\Router\Router;
use Labrador\Web\Router\RoutingResolution;
use Labrador\Web\Router\RoutingResolutionReason;
use Psr\Log\LoggerInterface;
use Ramsey\Uuid\Uuid;
use Throwable;

final class AmpApplication implements Application, RequestHandler {

    private readonly Middleware\AccessLoggerMiddleware $accessLoggerMiddleware;

    public function __construct(
        private readonly HttpServer $httpServer,
        private readonly ErrorHandler $errorHandler,
        private readonly Router $router,
        private readonly GlobalMiddlewareCollection $globalMiddlewareCollection,
        private readonly Emitter $emitter,
        private readonly LoggerInterface $logger,
        private readonly RequestAnalyticsQueue $analyticsQueue,
        private readonly PreciseTime $preciseTime,
    ) {
        $this->accessLoggerMiddleware = new Middleware\AccessLoggerMiddleware($this->logger);
    }

    public function start() : void {
        $this->logger->info('Labrador HTTP application starting up.');
        $this->emitter->emit(new ApplicationStarted($this))->await();

        $this->logger->debug('Allowing global middleware to be added through event system.');
        $this->emitter->emit(new AddGlobalMiddleware($this->globalMiddlewareCollection))->await();

        $this->logger->debug('Allowing routes to be added through event system.');
        $this->emitter->emit(new AddRoutes($this->router))->await();

        $this->httpServer->start($this, $this->errorHandler);

        $this->logger->info('Application server is responding to requests.');
        $this->emitter->emit(new ReceivingConnections($this->httpServer))->await();
    }

    public function stop() : void {
        $this->httpServer->stop();

        $this->emitter->emit(new ApplicationStopped($this))->await();

        $this->logger->info('Labrador HTTP application stopping.');
    }

    public function handleRequest(Request $request) : Response {
        $benchmark = RequestBenchmark::requestReceived($request, $this->preciseTime);

        try {
            $requestId = Uuid::uuid6();
            $request->setAttribute(RequestAttribute::RequestId->value, $requestId);

            $this->emitter->queue(new RequestReceived($request));

            $routingResolution = $this->routeRequest($request, $benchmark);

            if ($routingResolution->reason === RoutingResolutionReason::NotFound) {
                $response = $this->errorHandler->handleError(HttpStatus::NOT_FOUND, 'Not Found', $request);
            } elseif ($routingResolution->reason === RoutingResolutionReason::MethodNotAllowed) {
                $response = $this->errorHandler->handleError(HttpStatus::METHOD_NOT_ALLOWED, 'Method Not Allowed', $request);
            } else {
                $requestHandler = $routingResolution->requestHandler;

                assert($requestHandler instanceof RequestHandler);

                $request->setAttribute(RequestAttribute::RequestHandler->value, $requestHandler);

                $this->emitter->queue(new WillInvokeRequestHandler($requestHandler, $request));

                $handler = $this->getMiddlewareStack($requestHandler, $routingResolution->middleware, $benchmark);

                $response = $handler->handleRequest($request);
            }

            $this->emitter->queue(new ResponseSent($request, $response));

            $this->analyticsQueue->queue($benchmark->responseSent($response));

            return $response;
        } catch (Throwable $throwable) {
            $this->logger->error(
                '{exception_class} thrown in {file}#L{line_number} handling client {client_address} with request "{method} {path}". Message: {exception_message}',
                [
                    'client_address' => $request->getClient()->getRemoteAddress()->toString(),
                    'method' => $request->getMethod(),
                    'path' => $request->getUri()->getPath(),
                    'exception_class' => $throwable::class,
                    'file' => $throwable->getFile(),
                    'line_number' => $throwable->getLine(),
                    'exception_message' => $throwable->getMessage(),
                    'stack_trace' => $throwable->getTrace()
                ]
            );

            $this->analyticsQueue->queue($benchmark->exceptionThrown($throwable));

            return $this->errorHandler->handleError(
                HttpStatus::INTERNAL_SERVER_ERROR,
                'Internal Server Error',
                $request
            );
        }
    }

    private function routeRequest(Request $request, RequestBenchmark $benchmark) : RoutingResolution {
        $benchmark->routingStarted();
        $routingResolution = $this->router->match($request);
        $benchmark->routingCompleted($routingResolution->reason);
        return $routingResolution;
    }

    /**
     * @param RequestHandler $requestHandler
     * @param list<Middleware> $routeMiddlewareCollection
     * @param RequestBenchmark $benchmark
     * @return RequestHandler
     */
    private function getMiddlewareStack(RequestHandler $requestHandler, array $routeMiddlewareCollection, RequestBenchmark $benchmark) : RequestHandler {
        $middlewares = [];
        $middlewares[] = $this->benchmarkMiddlewareProcessingStartedMiddleware($benchmark);
        $middlewares[] = $this->accessLoggerMiddleware;

        foreach ($this->globalMiddlewareCollection as $globalMiddleware) {
            $middlewares[] = $globalMiddleware;
        }

        foreach ($routeMiddlewareCollection as $routeMiddleware) {
            $middlewares[] = $routeMiddleware;
        }

        $middlewares[] = $this->finalMiddlewareProcessingMiddleware($benchmark);

        return Middleware\stackMiddleware($requestHandler, ...$middlewares);
    }

    private function benchmarkMiddlewareProcessingStartedMiddleware(RequestBenchmark $benchmark) : Middleware {
        return new class($benchmark) implements Middleware {
            public function __construct(
                private readonly RequestBenchmark $benchmark
            ) {
            }

            public function handleRequest(Request $request, RequestHandler $requestHandler) : Response {
                $this->benchmark->middlewareProcessingStarted();
                return $requestHandler->handleRequest($request);
            }
        };
    }

    private function finalMiddlewareProcessingMiddleware(RequestBenchmark $benchmark) : Middleware {
        return new class($benchmark) implements Middleware {

            public function __construct(
                private readonly RequestBenchmark $benchmark,
            ) {
            }

            public function handleRequest(Request $request, RequestHandler $requestHandler) : Response {
                $this->benchmark->middlewareProcessingCompleted();
                $this->benchmark->requestHandlerProcessingStarted($requestHandler);

                return $requestHandler->handleRequest($request);
            }
        };
    }
}
