<?php

namespace Cspray\Labrador\Http\Test\Unit\Stub;

use Amp\CompositeException;
use Amp\Http\Server\ErrorHandler;
use Amp\Http\Server\HttpServer;
use Amp\Http\Server\HttpServerStatus;
use Amp\Http\Server\Request;
use Amp\Http\Server\RequestHandler;
use Amp\Http\Server\Response;
use Amp\Socket\SocketServer;
use function Amp\async;
use function Amp\Future\awaitAll;

final class HttpServerStub implements HttpServer {

    private array $onStart = [];
    private array $onStop = [];

    private array $servers = [];

    private HttpServerStatus $status;

    private ?RequestHandler $requestHandler = null;
    private ?ErrorHandler $errorHandler = null;

    public function __construct() {
        $this->status = HttpServerStatus::Stopped;
    }

    public function onStart(\Closure $onStart) : void {
        $this->onStart[] = $onStart;
    }

    public function onStop(\Closure $onStop) : void {
        $this->onStop[] = $onStop;
    }

    public function getServers() : array {
        return $this->servers;
    }

    public function setServers(SocketServer... $socketServers) : void {
        $this->servers = $socketServers;
    }

    public function getStatus() : HttpServerStatus {
        return $this->status;
    }

    public function setStatus(HttpServerStatus $status) : void {
        $this->status = $status;
    }

    public function receiveRequest(Request $request) : Response {
        try {
            if ($this->getStatus() !== HttpServerStatus::Started) {
                throw new \RuntimeException('Unable to receive requests until the server has started');
            }
            return $this->requestHandler->handleRequest($request);
        } catch (\Throwable $throwable) {
            return $this->errorHandler->handleError(500, 'Internal Server Error', $request);
        }
    }

    public function start(RequestHandler $handler, ErrorHandler $errorHandler) : void {
        $this->requestHandler = $handler;
        $this->errorHandler = $errorHandler;

        $this->status = HttpServerStatus::Starting;

        $futures = [];
        foreach ($this->onStart as $callable) {
            $futures[] = async($callable, $this);
        }

        [$exceptions] = awaitAll($futures);

        if (count($exceptions) !== 0) {
            throw new CompositeException($exceptions);
        }

        $this->status = HttpServerStatus::Started;
    }

    public function stop() : void {
        $this->status = HttpServerStatus::Stopping;

        $futures = [];
        foreach ($this->onStop as $callable) {
            $futures[] = async($callable, $this);
        }

        [$exceptions] = awaitAll($futures);

        $this->status = HttpServerStatus::Stopped;

        if (count($exceptions) !== 0) {
            throw new CompositeException($exceptions);
        }
    }
}