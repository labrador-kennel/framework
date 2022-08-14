<?php declare(strict_types=1);

namespace Cspray\Labrador\Http;

use Amp\Http\Server\ErrorHandler;
use Amp\Http\Server\HttpServer;
use Amp\Http\Server\Middleware;
use Amp\Http\Server\Request;
use Amp\Http\Server\RequestHandler;
use Amp\Http\Server\Response;
use Cspray\AnnotatedContainer\Attribute\Service;
use Cspray\Labrador\AsyncEvent\EventEmitter;
use Cspray\Labrador\Http\Event\AddRoutesEvent;
use Cspray\Labrador\Http\Event\ApplicationStartedEvent;
use Cspray\Labrador\Http\Event\ControllerInvokedEvent;
use Cspray\Labrador\Http\Event\ReceivingConnectionsEvent;
use Cspray\Labrador\Http\Event\RequestReceivedEvent;
use Cspray\Labrador\Http\Event\ResponseSentEvent;
use Cspray\Labrador\Http\Http\RequestAttribute;
use Cspray\Labrador\Http\Router\Router;
use Psr\Log\LoggerInterface;
use Ramsey\Uuid\Uuid;

#[Service]
final class Application implements RequestHandler {

    public function __construct(
        private readonly HttpServer $httpServer,
        private readonly ErrorHandler $errorHandler,
        private readonly Router $router,
        private readonly EventEmitter $emitter,
        private readonly LoggerInterface $logger
    ) {}

    public function getRouter() : Router {
        return $this->router;
    }

    public function addMiddleware(Middleware $middleware) : void {

    }

    public function start() : void {
        $this->emitter->emit(new ApplicationStartedEvent($this))->await();
        $this->emitter->emit(new AddRoutesEvent($this->router))->await();

        $this->httpServer->start($this, $this->errorHandler);

        $this->emitter->emit(new ReceivingConnectionsEvent($this->httpServer))->await();
    }

    public function stop() : void {
    }

    public function handleRequest(Request $request) : Response {
        $requestId =  Uuid::uuid6();
        $request->setAttribute(RequestAttribute::RequestId->value, $requestId);

        $this->emitter->emit(new RequestReceivedEvent($request))->await();
        $controller = $this->router->match($request);
        $this->emitter->emit(new ControllerInvokedEvent($controller, $requestId))->await();

        $response = $controller->handleRequest($request);
        $this->emitter->emit(new ResponseSentEvent($response, $requestId))->await();
        return $response;
    }
}
