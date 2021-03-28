<?php declare(strict_types=1);

namespace Cspray\Labrador\Http\Test;

use Amp\Http\Client\HttpClientBuilder;
use Amp\Http\Client\HttpClient;
use Amp\Http\Client\Request as ClientRequest;
use Amp\Http\Client\Response as ClientResponse;
use Amp\Http\Server\Middleware;
use Amp\Http\Server\Request as ServerRequest;
use Amp\Http\Server\Response as ServerResponse;
use Amp\Http\Server\RequestHandler;
use Amp\Http\Status;
use Amp\PHPUnit\AsyncTestCase;
use Amp\Promise;
use Auryn\Injector;
use Cspray\Labrador\AsyncEvent\AmpEventEmitter;
use Cspray\Labrador\Plugin\PluginManager;
use Amp\Socket\Server as SocketServer;
use Amp\Socket\SocketException;
use Amp\Success;
use Cspray\Labrador\Http\DefaultHttpApplication;
use Cspray\Labrador\Http\Router\FastRouteRouter;
use Cspray\Labrador\Http\Router\Router;
use Cspray\Labrador\Http\Test\Stub\ErrorThrowingController;
use Cspray\Labrador\Http\Test\Stub\ResponseControllerStub;
use FastRoute\DataGenerator\GroupCountBased as GcbDataGenerator;
use FastRoute\Dispatcher\GroupCountBased as GcbDispatcher;
use FastRoute\RouteCollector;
use FastRoute\RouteParser\Std as StdRouteParser;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

class HttpApplicationTest extends AsyncTestCase {

    /**
     * @var SocketServer
     */
    private $socketServer;

    /**
     * @var HttpClient
     */
    private $client;

    private $pluginManager;

    private $application;

    private $router;

    /**
     * @throws SocketException
     */
    public function setUpAsync() {
        parent::setUpAsync();
        $this->setTimeout(1500);
        $this->socketServer = SocketServer::listen('tcp://127.0.0.1:0');
        $this->client = HttpClientBuilder::buildDefault();
        $emitter = new AmpEventEmitter();
        $injector = new Injector();
        $this->pluginManager = new PluginManager($injector, $emitter);
        $this->router = $this->getRouter();
        $this->application = new DefaultHttpApplication(
            $this->pluginManager,
            $this->router,
            $this->socketServer
        );
    }

    public function tearDownAsync() {
        yield $this->application->stop();
    }

    private function getRouter() {
        return new FastRouteRouter(
            new RouteCollector(new StdRouteParser(), new GcbDataGenerator()),
            function($data) { return new GcbDispatcher($data);
            }
        );
    }

    private function registerRoutes(Router $router) {
        $controller = new ResponseControllerStub(new ServerResponse(200, [], 'From controller'));
        $errorController = new ErrorThrowingController();
        $router->addRoute('GET', '/foo', $controller);
        $router->addRoute('GET', '/throw-error', $errorController);
    }

    public function testBasicRouteFound() {
        $this->application->setLogger(new NullLogger());
        $this->registerRoutes($this->router);

        yield $this->application->start();

        /** @var ClientResponse $response */
        $response = yield $this->client->request(
            new ClientRequest('http://' . $this->socketServer->getAddress() . '/foo')
        );
        $body = yield $response->getBody()->buffer();

        $this->assertSame(Status::OK, $response->getStatus());
        $this->assertSame('From controller', $body);
    }

    public function testRouteNotFound() {
        $this->application->setLogger(new NullLogger());
        $this->registerRoutes($this->router);
        yield $this->application->start();

        /** @var ClientResponse $response */
        $response = yield $this->client->request(
            new ClientRequest('http://' . $this->socketServer->getAddress() . '/bar')
        );
        $body = yield $response->getBody()->buffer();

        $this->assertSame(Status::NOT_FOUND, $response->getStatus());
        $this->assertSame('Not Found', $body);
    }

    public function testHandlesErrorGracefully() {
        $this->application->setLogger(new NullLogger());
        $this->registerRoutes($this->router);

        yield $this->application->start();

        $url = 'http://' . $this->socketServer->getAddress() . '/throw-error';
        /** @var ClientResponse $response */
        $response = yield $this->client->request(new ClientRequest($url));

        $this->assertSame(Status::INTERNAL_SERVER_ERROR, $response->getStatus());

        /** @var ClientResponse $response */
        $response = yield $this->client->request(
            new ClientRequest('http://' . $this->socketServer->getAddress() . '/foo')
        );
        $body = yield $response->getBody()->buffer();

        $this->assertSame(Status::OK, $response->getStatus());
        $this->assertSame('From controller', $body);
    }

    public function testErrorResponseReturnedFromApplication() {
        $this->application->setLogger(new NullLogger());
        $this->application->setExceptionToResponseHandler(function(\Throwable $error) {
            return new ServerResponse(Status::SERVICE_UNAVAILABLE);
        });

        $this->registerRoutes($this->router);
        yield $this->application->start();

        $url = 'http://' . $this->socketServer->getAddress() . '/throw-error';
        /** @var ClientResponse $response */
        $response = yield $this->client->request(new ClientRequest($url));

        $this->assertSame(Status::SERVICE_UNAVAILABLE, $response->getStatus());
    }

    public function testErrorLogged() {
        $logger = $this->createMock(LoggerInterface::class);
        $expectedMsg = 'Exception thrown processing GET http://' . $this->socketServer->getAddress() . '/throw-error.';
        $expectedMsg .= ' Message: Controller thrown exception';
        $logger->expects($this->once())
               ->method('critical')
               ->with($expectedMsg, $this->callback(function($secondArg) {
                    $exception = $secondArg['exception'] ?? null;
                    return $exception instanceof \Exception &&
                        $exception->getMessage() === 'Controller thrown exception';
               }));
        $this->application->setLogger($logger);

        $this->registerRoutes($this->router);
        yield $this->application->start();

        yield $this->client->request(new ClientRequest('http://' . $this->socketServer->getAddress() . '/throw-error'));
    }

    public function testAddingMiddlewareCanShortCircuitRouterMatching() {
        $router = $this->createMock(Router::class);
        $router->expects($this->never())->method('match');
        $logger = new NullLogger();
        $this->application->setLogger($logger);
        $reflectedApp = new \ReflectionObject($this->application);
        $reflectedProp = $reflectedApp->getProperty('router');
        $reflectedProp->setAccessible(true);
        $reflectedProp->setValue($this->application, $router);

        $middleware = new class implements Middleware {

            /**
             * @param ServerRequest $request
             * @param RequestHandler $requestHandler
             *
             * @return Promise<ServerResponse>
             */
            public function handleRequest(ServerRequest $request, RequestHandler $requestHandler): Promise {
                $response = new ServerResponse(Status::ACCEPTED, [], 'Short circuited router');
                return new Success($response);
            }
        };
        $this->application->addMiddleware($middleware);

        yield $this->application->start();

        /** @var ClientResponse $response */
        $url = 'http://' . $this->socketServer->getAddress() . '/does_not_matter';
        $response = yield $this->client->request(new ClientRequest($url));
        $body = yield $response->getBody()->buffer();

        $this->assertSame(Status::ACCEPTED, $response->getStatus());
        $this->assertSame('Short circuited router', $body);
    }
}
