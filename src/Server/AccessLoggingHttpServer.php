<?php declare(strict_types=1);

namespace Labrador\Http\Server;

use Amp\Http\Server\ErrorHandler;
use Amp\Http\Server\HttpServer;
use Amp\Http\Server\HttpServerStatus;
use Amp\Http\Server\Request;
use Amp\Http\Server\RequestHandler;
use Amp\Http\Server\Response;
use Closure;
use Psr\Log\LoggerInterface;

class AccessLoggingHttpServer implements HttpServer {

    public function __construct(
        private readonly HttpServer $server,
        private readonly LoggerInterface $logger
    ) {}

    public function start(RequestHandler $requestHandler, ErrorHandler $errorHandler) : void {
        $this->server->start($requestHandler, $errorHandler);
    }

    public function stop() : void {
        $this->server->stop();
    }

    public function onStart(Closure $onStart) : void {
        $this->server->onStart($onStart);
    }

    public function onStop(Closure $onStop) : void {
        $this->server->onStop($onStop);
    }

    public function getStatus() : HttpServerStatus {
        return $this->server->getStatus();
    }

    private function accessLoggingRequestHandler(RequestHandler $requestHandler) : RequestHandler {
        return new class($requestHandler) implements RequestHandler {

            public function handleRequest(Request $request) : Response {
                // TODO: Implement handleRequest() method.
            }
        };
    }
}