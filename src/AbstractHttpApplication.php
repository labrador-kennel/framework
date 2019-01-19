<?php declare(strict_types=1);

namespace Cspray\Labrador\Http;

use Amp\Http\Server\RequestHandler\CallableRequestHandler;
use Amp\Http\Server\Server as HttpServer;
use Amp\Http\Server\Request;
use Amp\Http\Server\Response;
use Amp\Promise;
use Amp\Socket\Server as SocketServer;
use Cspray\Labrador\Http\Router\Router;
use Cspray\Labrador\StandardApplication;
use function Amp\call;
use Psr\Log\LoggerInterface;

abstract class AbstractHttpApplication extends StandardApplication {

    private $logger;
    private $router;

    public function __construct(LoggerInterface $logger, Router $router) {
        $this->logger = $logger;
        $this->router = $router;
    }

    /**
     * Perform whatever logic or operations your application requires; return a Promise that resolves when you app is
     * finished running.
     *
     * This method should avoid throwing an exception and instead fail the Promise with the Exception that caused the
     * application to crash.
     *
     * @return Promise
     */
    final public function execute() : Promise {
        return call(function() {
            $httpServer = new HttpServer($this->getSocketServers(), new CallableRequestHandler(function(Request $request) {
                try {
                    $controller = $this->router->match($request);
                    return $controller->handleRequest($request);
                } catch (\Throwable $error) {
                    return $this->exceptionToResponse($error);
                }
            }), $this->logger);

            yield $httpServer->start();
        });
    }

    protected function exceptionToResponse(\Throwable $throwable) : Response {
        return new Response(StatusCodes::INTERNAL_SERVER_ERROR);
    }

    /**
     * @return SocketServer[]
     */
    abstract protected function getSocketServers() : array;


}