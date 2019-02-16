<?php declare(strict_types=1);

namespace Cspray\Labrador\Http;

use Cspray\Labrador\Http\Router\Router;
use Cspray\Labrador\StandardApplication;

use Amp\Http\Status;
use Amp\Http\Server\Middleware;
use Amp\Http\Server\RequestHandler\CallableRequestHandler;
use Amp\Http\Server\Request;
use Amp\Http\Server\Response;
use Amp\Http\Server\Server as HttpServer;
use Amp\Promise;
use Amp\Socket\Server as SocketServer;
use Psr\Log\LoggerInterface;

use function Amp\call;

final class HttpApplication extends StandardApplication {

    private $logger;
    private $router;
    private $socketServers;
    private $exceptionToResponseHandler;
    private $middlewares = [];

    public function __construct(LoggerInterface $logger, Router $router, SocketServer ...$socketServers) {
        $this->logger = $logger;
        $this->router = $router;
        $this->socketServers = $socketServers;
        $this->exceptionToResponseHandler = function(/* Throwable $error */) {
            return new Response(Status::INTERNAL_SERVER_ERROR);
        };
    }

    /**
     * Add a Middleware that will be invoked _before_ the application attempts to match the given Request to the Router
     * AND will be invoked for _every_ request.
     *
     * Generally speaking you should avoid adding too many Middleware to this and let the Router and Controllers control
     * your application flow, pun intended. Instead you should reserve this for low-level aspects of HTTP Requests. One
     * of the primary use cases for this Middleware is to support CORS before the Router has an opportunity to send a
     * Method Not Allowed response for OPTIONS requests.
     *
     * @param Middleware $middleware
     */
    public function addMiddleware(Middleware $middleware) : void {
        $this->middlewares[] = $middleware;
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
        $applicationHandler = new CallableRequestHandler(function(Request $request) {
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
        });
        return call(function() use($applicationHandler) {
            $handler = Middleware\stack($applicationHandler, ...$this->middlewares);
            $httpServer = new HttpServer($this->socketServers, $handler, $this->logger);

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
