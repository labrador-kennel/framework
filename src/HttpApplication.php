<?php declare(strict_types=1);

namespace Cspray\Labrador\Http;

use Cspray\Labrador\Http\Router\Router;
use Cspray\Labrador\StandardApplication;

use Amp\Http\Status;
use Amp\Http\Server\{
    RequestHandler\CallableRequestHandler,
    Request,
    Response,
    Server as HttpServer,
};
use Amp\Promise;
use Amp\Socket\Server as SocketServer;
use Psr\Log\LoggerInterface;

use function Amp\call;

final class HttpApplication extends StandardApplication {

    private $logger;
    private $router;
    private $socketServers;
    private $exceptionToResponseHandler;

    public function __construct(LoggerInterface $logger, Router $router, SocketServer ...$socketServers) {
        $this->logger = $logger;
        $this->router = $router;
        $this->socketServers = $socketServers;
        $this->exceptionToResponseHandler = function(\Throwable $error) {
            return new Response(Status::INTERNAL_SERVER_ERROR);
        };
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
            $httpServer = new HttpServer($this->socketServers, new CallableRequestHandler(function(Request $request) {
                try {
                    $controller = $this->router->match($request);
                    $response = yield $controller->handleRequest($request);
                    return $response;
                } catch (\Throwable $error) {
                    $msgFormat = 'Exception thrown processing %s %s. Message: %s';
                    $msg = sprintf($msgFormat, $request->getMethod(), $request->getUri(), $error->getMessage());
                    $this->logger->critical($msg, ['exception' => $error]);
                    return $this->exceptionToResponse($error);
                }
            }), $this->logger);

            yield $httpServer->start();
        });
    }

    public function setExceptionToResponseHandler(callable $callback) : void {
        $this->exceptionToResponseHandler = $callback;
    }

    protected function exceptionToResponse(\Throwable $throwable) : Response {
        return ($this->exceptionToResponseHandler)($throwable);
    }

}