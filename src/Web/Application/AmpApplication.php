<?php declare(strict_types=1);

namespace Labrador\Web\Application;

use Amp\Http\HttpStatus;
use Amp\Http\Server\ErrorHandler;
use Amp\Http\Server\HttpServer;
use Amp\Http\Server\Middleware;
use Amp\Http\Server\Request;
use Amp\Http\Server\RequestHandler;
use Amp\Http\Server\Response;
use Amp\Http\Server\StaticContent\DocumentRoot;
use Labrador\AsyncEvent\EventEmitter;
use Labrador\Web\Application\Analytics\PreciseTime;
use Labrador\Web\Application\Analytics\RequestAnalyticsQueue;
use Labrador\Web\Application\Analytics\RequestBenchmark;
use Labrador\Web\Controller\Controller;
use Labrador\Web\Controller\StaticAssetController;
use Labrador\Web\ErrorHandlerFactory;
use Labrador\Web\Event\AddRoutes;
use Labrador\Web\Event\ApplicationStarted;
use Labrador\Web\Event\ApplicationStopped;
use Labrador\Web\Event\ReceivingConnections;
use Labrador\Web\Event\RequestReceived;
use Labrador\Web\Event\ResponseSent;
use Labrador\Web\Event\WillInvokeController;
use Labrador\Web\Middleware\Priority;
use Labrador\Web\RequestAttribute;
use Labrador\Web\Router\Mapping\GetMapping;
use Labrador\Web\Router\Router;
use Labrador\Web\Router\RoutingResolution;
use Labrador\Web\Router\RoutingResolutionReason;
use Psr\Log\LoggerInterface;
use Ramsey\Uuid\Uuid;
use Throwable;

final class AmpApplication implements Application, RequestHandler {

    /**
     * @var array<string, Middleware[]>
     */
    private array $middleware = [];

    private ?ErrorHandler $errorHandler = null;

    private bool $isSessionSupported;

    public function __construct(
        private readonly HttpServer                 $httpServer,
        private readonly ErrorHandlerFactory $errorHandlerFactory,
        private readonly Router                     $router,
        private readonly EventEmitter               $emitter,
        private readonly LoggerInterface            $logger,
        private readonly ApplicationFeatures        $features,
        private readonly RequestAnalyticsQueue      $analyticsQueue,
        private readonly PreciseTime                $preciseTime,
    ) {
        $this->handleApplicationFeaturesSetup();
    }

    private function handleApplicationFeaturesSetup() : void {
        $sessionMiddleware = $this->features->getSessionMiddleware();
        if ($this->isSessionSupported = ($sessionMiddleware !== null)) {
            $this->addMiddleware(
                $sessionMiddleware,
                Priority::Critical
            );
        }

        $staticAssetSettings = $this->features->getStaticAssetSettings();
        if ($staticAssetSettings !== null) {
            $this->router->addRoute(
                new GetMapping(sprintf('/%s/{path:.+}', $staticAssetSettings->pathPrefix)),
                new StaticAssetController(
                    new DocumentRoot(
                        $this->httpServer,
                        $this->getErrorHandler(),
                        $staticAssetSettings->assetDir
                    ),
                    $this->getErrorHandler()
                )
            );
        }
    }

    public function getRouter() : Router {
        return $this->router;
    }

    public function addMiddleware(Middleware $middleware, Priority $priority = Priority::Low) : void {
        if (!isset($this->middleware[$priority->name])) {
            $this->middleware[$priority->name] = [];
        }

        $this->middleware[$priority->name][] = $middleware;
    }

    public function start() : void {
        $this->logger->info('Labrador HTTP application starting up.');
        $this->emitter->emit(new ApplicationStarted($this))->await();
        $this->logger->debug('Allowing routes to be added through event system.');
        $this->emitter->emit(new AddRoutes($this->router))->await();

        $this->httpServer->start($this, $this->getErrorHandler());

        $this->logger->info('Application server is responding to requests.');
        $this->emitter->emit(new ReceivingConnections($this->httpServer))->await();
    }

    public function stop() : void {
        $this->httpServer->stop();

        $this->emitter->emit(new ApplicationStopped($this))->await();

        $this->logger->info('Labrador HTTP application stopping.');
    }

    public function handleRequest(Request $request) : Response {
        if ($this->features->autoRedirectHttpToHttps() && $request->getUri()->getScheme() === 'http') {
            $uri = $request->getUri()->withScheme('https');
            $uri = $uri->withPort($this->features->getHttpsRedirectPort());
            return new Response(
                status: HttpStatus::MOVED_PERMANENTLY,
                headers: ['Location' => (string) $uri]
            );
        }

        $benchmark = RequestBenchmark::requestReceived($request, $this->preciseTime);

        try {
            $requestId = Uuid::uuid6();
            $request->setAttribute(RequestAttribute::RequestId->value, $requestId);

            $this->emitter->queue(new RequestReceived($request));

            $routingResolution = $this->routeRequest($request, $benchmark);

            if ($routingResolution->reason === RoutingResolutionReason::NotFound) {
                $response = $this->getErrorHandler()->handleError(HttpStatus::NOT_FOUND, 'Not Found', $request);
            } else if ($routingResolution->reason === RoutingResolutionReason::MethodNotAllowed) {
                $response = $this->getErrorHandler()->handleError(HttpStatus::METHOD_NOT_ALLOWED, 'Method Not Allowed', $request);
            } else {
                $controller = $routingResolution->controller;

                assert($controller instanceof Controller);

                $request->setAttribute(RequestAttribute::Controller->value, $controller);

                $this->emitter->queue(new WillInvokeController($controller, $requestId));

                $handler = $this->getMiddlewareStack($controller, $benchmark);

                $response = $handler->handleRequest($request);
            }

            $this->emitter->queue(new ResponseSent($response, $requestId));

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

            return $this->getErrorHandler()->handleError(
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

    private function getMiddlewareStack(Controller $controller, RequestBenchmark $benchmark) : RequestHandler {
        $middlewares = [];
        $middlewares[] = $this->benchmarkMiddlewareProcessingStartedMiddleware($benchmark);

        foreach (Priority::cases() as $priority) {
            $priorityMiddleware = $this->middleware[$priority->name] ?? [];
            foreach ($priorityMiddleware as $middleware)  {
                $middlewares[] = $middleware;
            }
        }

        $middlewares[] = $this->finalMiddlewareProcessingMiddleware($benchmark);

        return Middleware\stack($controller, ...$middlewares);
    }

    private function benchmarkMiddlewareProcessingStartedMiddleware(RequestBenchmark $benchmark) : Middleware {
        return new class($benchmark) implements Middleware {
            public function __construct(
                private readonly RequestBenchmark $benchmark
            ) {}

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
            ) {}

            public function handleRequest(Request $request, RequestHandler $requestHandler) : Response {
                if (!$requestHandler instanceof Controller) {
                    throw new \RuntimeException(
                        'An internal error within Labrador has occurred. Please ensure the Controller Session handling Middleware is executed last.'
                    );
                }

                $this->benchmark->middlewareProcessingCompleted();
                $this->benchmark->controllerProcessingStarted($requestHandler);

                return $requestHandler->handleRequest($request);
            }
        };
    }

    private function getErrorHandler() : ErrorHandler {
        if ($this->errorHandler === null) {
            $this->errorHandler = $this->errorHandlerFactory->createErrorHandler();
        }

        return $this->errorHandler;
    }
}
