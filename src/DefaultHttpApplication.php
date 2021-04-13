<?php declare(strict_types=1);

namespace Cspray\Labrador\Http;

use Amp\Deferred;
use Amp\Http\Server\ServerObserver;
use Amp\Loop;
use Amp\Success;
use Cspray\Labrador\ApplicationState;
use Cspray\Labrador\AsyncEvent\EventEmitter;
use Cspray\Labrador\Http\Event\HttpApplicationStartedEvent;
use Cspray\Labrador\Http\Event\HttpApplicationStoppedEvent;
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

use Throwable;
use function Amp\call;

class DefaultHttpApplication extends AbstractApplication implements HttpApplication {

    private $eventEmitter;
    private $router;
    private $socketServers;
    private $exceptionToResponseHandler;
    private $middlewares = [];

    private $httpServerDeferred = null;

    /**
     * @var HttpServer
     */
    private $httpServer;

    public function __construct(
        Pluggable $pluginManager,
        EventEmitter $eventEmitter,
        Router $router,
        SocketServer ...$socketServers
    ) {
        parent::__construct($pluginManager);
        $this->eventEmitter = $eventEmitter;
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
    protected function doStart() : Promise {
        $this->httpServerDeferred = new Deferred();

        Loop::defer(function() {
            $applicationHandler = new CallableRequestHandler(function(Request $request) {
                try {
                    $controller = $this->router->match($request);
                    $this->logger->info(sprintf(
                        'Using "%s" Controller for %s %s',
                        get_class($controller),
                        $request->getMethod(),
                        $request->getUri()->getPath()
                    ));
                    return yield $controller->handleRequest($request);
                } catch (Throwable $error) {
                    $msgFormat = 'Exception thrown processing %s %s. Message: %s';
                    $msg = sprintf($msgFormat, $request->getMethod(), $request->getUri(), $error->getMessage());
                    $this->logger->critical($msg, ['exception' => $error]);
                    return $this->exceptionToResponse($error);
                }
            });
            $handler = Middleware\stack($applicationHandler, ...$this->middlewares);
            $this->httpServer = new HttpServer($this->socketServers, $handler, $this->logger);
            $this->httpServer->attach(
                new class($this, $this->eventEmitter) implements ServerObserver {

                    private $app;
                    private $eventEmitter;

                    public function __construct(HttpApplication $app, EventEmitter $eventEmitter) {
                        $this->app = $app;
                        $this->eventEmitter = $eventEmitter;
                    }

                    public function onStart(\Amp\Http\Server\HttpServer $server) : Promise {
                        return call(function() {
                            Loop::defer(function() {
                                $event = new HttpApplicationStartedEvent($this->app);
                                yield $this->eventEmitter->emit($event);
                            });
                        });
                    }

                    public function onStop(\Amp\Http\Server\HttpServer $server) : Promise {
                        return new Success();
                    }
                }
            );

            yield $this->httpServer->start();
        });

        return $this->httpServerDeferred->promise();
    }

    public function stop() : Promise {
        return call(function() {
            yield $this->httpServer->stop();
            $this->setState(ApplicationState::Stopped());
            yield $this->eventEmitter->emit(new HttpApplicationStoppedEvent($this));
            $this->httpServerDeferred->resolve();
        });
    }

    public function setExceptionToResponseHandler(callable $callable) : void {
        $this->exceptionToResponseHandler = $callable;
    }

    protected function exceptionToResponse(Throwable $throwable) : Response {
        return ($this->exceptionToResponseHandler)($throwable);
    }
}
