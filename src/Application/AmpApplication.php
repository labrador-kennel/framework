<?php declare(strict_types=1);

namespace Labrador\Http\Application;

use Amp\Http\HttpStatus;
use Amp\Http\Server\ErrorHandler;
use Amp\Http\Server\HttpServer;
use Amp\Http\Server\Middleware;
use Amp\Http\Server\Request;
use Amp\Http\Server\RequestHandler;
use Amp\Http\Server\Response;
use Amp\Http\Server\Session\Session;
use Labrador\AsyncEvent\EventEmitter;
use Labrador\Http\Controller\Controller;
use Labrador\Http\Controller\DtoController;
use Labrador\Http\Controller\RequireSession;
use Labrador\Http\Controller\SessionAccess;
use Labrador\Http\ErrorHandlerFactory;
use Labrador\Http\Event\AddRoutes;
use Labrador\Http\Event\ApplicationStarted;
use Labrador\Http\Event\ApplicationStopped;
use Labrador\Http\Event\ReceivingConnections;
use Labrador\Http\Event\RequestReceived;
use Labrador\Http\Event\ResponseSent;
use Labrador\Http\Event\WillInvokeController;
use Labrador\Http\Exception\SessionNotEnabled;
use Labrador\Http\Internal\ReflectionCache;
use Labrador\Http\Middleware\Priority;
use Labrador\Http\RequestAttribute;
use Labrador\Http\Router\Router;
use Labrador\Http\Router\RoutingResolutionReason;
use Psr\Log\LoggerInterface;
use Ramsey\Uuid\Uuid;
use ReflectionClass;
use ReflectionMethod;
use Revolt\EventLoop;

final class AmpApplication implements Application, RequestHandler {

    private readonly Middleware $controllerSessionMiddleware;

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
    ) {
        $this->handleApplicationFeaturesSetup();
        $this->controllerSessionMiddleware =  new class($this->isSessionSupported) implements Middleware {

            public function __construct(
                private readonly bool $isSessionSupported
            ) {}

            public function handleRequest(Request $request, RequestHandler $requestHandler) : Response {
                if (!$requestHandler instanceof Controller) {
                    throw new \RuntimeException(
                        'An internal error within Labrador has occurred. Please ensure the Controller Session handling Middleware is executed last.'
                    );
                }

                $sessionAccess = $this->getControllerSessionAccess($requestHandler);
                if (!$this->isSessionSupported && $sessionAccess !== null) {
                    throw SessionNotEnabled::fromSessionAccessRequired($requestHandler, $sessionAccess);
                }

                if ($this->isSessionSupported) {
                    $session = $request->getAttribute(Session::class);
                    assert($session instanceof Session);
                    if ($sessionAccess === SessionAccess::Read) {
                        $session->read();
                    } else if ($sessionAccess === SessionAccess::Write) {
                        $session->open();
                    }
                }

                $response = $requestHandler->handleRequest($request);

                if ($this->isSessionSupported) {
                    $sessionWrite = $request->getAttribute(Session::class);
                    assert($sessionWrite instanceof Session);
                    if ($sessionAccess === SessionAccess::Write) {
                        $sessionWrite->save();
                    }
                }

                return $response;
            }

            private function getControllerSessionAccess(Controller $controller) : ?SessionAccess {
                $method = null;
                if ($controller instanceof DtoController) {
                    [$controller, $method] = explode(
                        '::',
                        str_replace(['DtoHandler<', '>'], '', $controller->toString())
                    );
                }

                assert(is_object($controller) || class_exists($controller));

                $reflection = ReflectionCache::reflectionClass($controller);
                $sessionAccess = $this->getSessionAccessFromReflection($reflection);

                if ($sessionAccess === null && $method !== null) {
                    $sessionAccess = $this->getSessionAccessFromReflection($reflection->getMethod($method));
                }

                return $sessionAccess;
            }

            private function getSessionAccessFromReflection(ReflectionClass|ReflectionMethod $reflection) : ?SessionAccess {
                $requireSessionAttributes = $reflection->getAttributes(RequireSession::class, \ReflectionAttribute::IS_INSTANCEOF);
                $sessionAccess = null;
                if ($requireSessionAttributes !== []) {
                    $requireSession = $requireSessionAttributes[0]->newInstance();
                    $sessionAccess = $requireSession->access;
                }

                return $sessionAccess;
            }
        };
    }

    private function handleApplicationFeaturesSetup() : void {
        $sessionMiddleware = $this->features->getSessionMiddleware();
        if ($this->isSessionSupported = ($sessionMiddleware !== null)) {
            $this->addMiddleware(
                $sessionMiddleware,
                Priority::Critical
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
            return new Response(
                status: HttpStatus::SEE_OTHER,
                headers: ['Location' => (string) $request->getUri()->withScheme('https')]
            );
        }

        $requestId = Uuid::uuid6();
        $request->setAttribute(RequestAttribute::RequestId->value, $requestId);

        $this->emitter->queue(new RequestReceived($request));
        $routingResolution = $this->router->match($request);

        if ($routingResolution->reason === RoutingResolutionReason::NotFound) {
            $response = $this->getErrorHandler()->handleError(HttpStatus::NOT_FOUND, 'Not Found', $request);
        } else if ($routingResolution->reason === RoutingResolutionReason::MethodNotAllowed) {
            $response = $this->getErrorHandler()->handleError(HttpStatus::METHOD_NOT_ALLOWED, 'Method Not Allowed', $request);
            $path = $request->getUri()->getPath() === '' ? '/' : $request->getUri()->getPath();
        } else {
            $controller = $routingResolution->controller;

            assert($controller instanceof Controller);

            $this->emitter->queue(new WillInvokeController($controller, $requestId));

            $middlewares = [];
            foreach (Priority::cases() as $priority) {
                $priorityMiddleware = $this->middleware[$priority->name] ?? [];
                foreach ($priorityMiddleware as $middleware)  {
                    $middlewares[] = $middleware;
                }
            }

            // It is CRITICAL that this middleware runs last to ensure any Session attributes are handled properly
            $middlewares[] = $this->controllerSessionMiddleware;

            $response = Middleware\stack($controller, ...$middlewares)->handleRequest($request);
        }

        $this->emitter->queue(new ResponseSent($response, $requestId));

        return $response;
    }

    private function getErrorHandler() : ErrorHandler {
        if ($this->errorHandler === null) {
            $this->errorHandler = $this->errorHandlerFactory->createErrorHandler();
        }

        return $this->errorHandler;
    }
}
