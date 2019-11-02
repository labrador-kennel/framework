<?php declare(strict_types=1);

namespace Cspray\Labrador\Http;

use Amp\Deferred;
use Amp\Http\Server\ServerObserver;
use Amp\Success;
use Cspray\Labrador\Http\Router\Router;
use Cspray\Labrador\AbstractApplication;

use Amp\Http\Status;
use Amp\Http\Server\Middleware;
use Amp\Http\Server\RequestHandler\CallableRequestHandler;
use Amp\Http\Server\Request;
use Amp\Http\Server\Response;
use Amp\Http\Server\Server as HttpServer;
use Amp\Promise;
use Amp\Socket\Server as SocketServer;
use Cspray\Labrador\Plugin\Pluggable;

use function Amp\call;

final class HttpApplication extends AbstractApplication {

    private $router;
    private $socketServers;
    private $exceptionToResponseHandler;
    private $middlewares = [];
    private $httpServer;

    public function __construct(Pluggable $pluginManager, Router $router, SocketServer ...$socketServers) {
        parent::__construct($pluginManager);
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
    public function execute() : Promise {
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
            $deferred = new Deferred();

            $handler = Middleware\stack($applicationHandler, ...$this->middlewares);
            $this->httpServer = new HttpServer($this->socketServers, $handler, $this->logger);

            $this->httpServer->attach($this->getServerObserver($deferred));

            yield $this->httpServer->start();

            return $deferred->promise();
        });
    }

    public function setExceptionToResponseHandler(callable $callback) : void {
        $this->exceptionToResponseHandler = $callback;
    }

    private function exceptionToResponse(\Throwable $throwable) : Response {
        return ($this->exceptionToResponseHandler)($throwable);
    }

    private function getServerObserver(Deferred $deferred) : ServerObserver {
        return new class($deferred) implements ServerObserver {

            private $deferred;

            public function __construct(Deferred $deferred) {
                $this->deferred = $deferred;
            }

            /**
             * Invoked when the server is starting. Server sockets have been opened, but are not yet accepting client
             * connections. This method should be used to set up any necessary state for responding to requests,
             * including starting loop watchers such as timers.
             *
             * @param HttpServer $server
             *
             * @return Promise
             */
            public function onStart(HttpServer $server) : Promise {
                return new Success();
            }

            /**
             * Invoked when the server has initiated stopping. No further requests are accepted and any connected
             * clients should be closed gracefully and any loop watchers cancelled.
             *
             * @param HttpServer $server
             *
             * @return Promise
             */
            public function onStop(HttpServer $server) : Promise {
                $this->deferred->resolve();
                return new Success();
            }
        };
    }
}
