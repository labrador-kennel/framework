<?php declare(strict_types=1);

namespace Cspray\Labrador\Http;

use Amp\Http\Server\ErrorHandler;
use Amp\Http\Server\HttpServer;
use Amp\Http\Server\Middleware;
use Amp\Http\Server\Request;
use Amp\Http\Server\RequestHandler;
use Amp\Http\Server\Response;
use Amp\Http\Status;
use Cspray\AnnotatedContainer\Attribute\Service;
use Cspray\Labrador\AsyncEvent\EventEmitter;
use Cspray\Labrador\Http\Controller\Controller;
use Cspray\Labrador\Http\Event\AddRoutesEvent;
use Cspray\Labrador\Http\Event\ApplicationStartedEvent;
use Cspray\Labrador\Http\Event\ReceivingConnectionsEvent;
use Cspray\Labrador\Http\Event\RequestReceivedEvent;
use Cspray\Labrador\Http\Event\ResponseSentEvent;
use Cspray\Labrador\Http\Event\WillInvokeControllerEvent;
use Cspray\Labrador\Http\Middleware\Priority;
use Cspray\Labrador\Http\Router\Router;
use Cspray\Labrador\Http\Router\RoutingResolutionReason;
use Psr\Log\LoggerInterface;
use Ramsey\Uuid\Uuid;

#[Service]
final class AmpApplication implements Application, RequestHandler {

    /**
     * @var array<string, Middleware[]>
     */
    private array $middleware = [];
    private ?ErrorHandler $errorHandler = null;

    public function __construct(
        private readonly HttpServer $httpServer,
        private readonly ErrorHandlerFactory $errorHandlerFactory,
        private readonly Router $router,
        private readonly EventEmitter $emitter,
        private readonly LoggerInterface $logger
    ) {}

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
        $this->emitter->emit(new ApplicationStartedEvent($this))->await();
        $this->logger->info('Allowing routes to be added through event system.');
        $this->emitter->emit(new AddRoutesEvent($this->router))->await();

        $this->httpServer->start($this, $this->getErrorHandler());

        $this->logger->info('Application server is responding to requests.');
        $this->emitter->emit(new ReceivingConnectionsEvent($this->httpServer))->await();
    }

    public function stop() : void {
    }

    public function handleRequest(Request $request) : Response {
        $requestId =  Uuid::uuid6();
        $request->setAttribute(RequestAttribute::RequestId->value, $requestId);
        $this->logger->info(
            'Started processing {method} {url} - Request id: {requestId}.',
            [
                'method' => $request->getMethod(),
                'url' => (string) $request->getUri(),
                'requestId' => $requestId->toString()
            ]
        );

        $this->emitter->queue(new RequestReceivedEvent($request));
        $routingResolution = $this->router->match($request);

        if ($routingResolution->reason === RoutingResolutionReason::NotFound) {
            $response = $this->getErrorHandler()->handleError(Status::NOT_FOUND, 'Not Found', $request);
        } else if ($routingResolution->reason === RoutingResolutionReason::MethodNotAllowed) {
            $response = $this->getErrorHandler()->handleError(Status::METHOD_NOT_ALLOWED, 'Method Not Allowed', $request);
        } else {
            $controller = $routingResolution->controller;

            assert($controller instanceof Controller);

            $this->logger->info(
                'Found matching controller, {controller}, for Request id: {requestId}.',
                [
                    'controller' => $controller->toString(),
                    'requestId' => $requestId->toString()
                ]
            );

            $this->emitter->queue(new WillInvokeControllerEvent($controller, $requestId));

            $middlewares = [];
            foreach (Priority::cases() as $priority) {
                $priorityMiddleware = $this->middleware[$priority->name] ?? [];
                foreach ($priorityMiddleware as $middleware)  {
                    $middlewares[] = $middleware;
                }
            }

            $response = Middleware\stack($controller, ...$middlewares)->handleRequest($request);

            $this->emitter->queue(new ResponseSentEvent($response, $requestId));
        }

        return $response;
    }

    private function getErrorHandler() : ErrorHandler {
        if ($this->errorHandler === null) {
            $this->errorHandler = $this->errorHandlerFactory->createErrorHandler();
        }

        return $this->errorHandler;
    }
}
