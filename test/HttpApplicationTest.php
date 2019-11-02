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
use Amp\Promise;
use Auryn\Injector;
use Cspray\Labrador\AsyncEvent\AmpEmitter;
use Cspray\Labrador\Plugin\PluginManager;
use Amp\Socket\Server as SocketServer;
use Amp\Socket\SocketException;
use Amp\Success;
use Cspray\Labrador\Http\HttpApplication;
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

    /**
     * @throws SocketException
     */
    public function setUp() {
        parent::setUp();
        $this->timeout(1500);
        $this->socketServer = SocketServer::listen('tcp://127.0.0.1:0');
        $this->client = HttpClientBuilder::buildDefault();
        $emitter = new AmpEmitter();
        $injector = new Injector();
        $this->pluginManager = new PluginManager($injector, $emitter);
    }

    public function tearDown() {
        parent::tearDown();
        $this->socketServer->close();
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
        $router = $this->getRouter();
        $application = new HttpApplication($this->pluginManager, $router, $this->socketServer);
        $application->setLogger(new NullLogger());
        $this->registerRoutes($router);

        yield $application->execute();

        /** @var ClientResponse $response */
        $response = yield $this->client->request(
            new ClientRequest('http://' . $this->socketServer->getAddress() . '/foo')
        );
        $body = yield $response->getBody()->buffer();

        $this->assertSame(Status::OK, $response->getStatus());
        $this->assertSame('From controller', $body);
    }

    public function testRouteNotFound() {
        $router = $this->getRouter();
        $application = new HttpApplication($this->pluginManager, $router, $this->socketServer);
        $application->setLogger(new NullLogger());
        $this->registerRoutes($router);
        yield $application->execute();

        /** @var ClientResponse $response */
        $response = yield $this->client->request(
            new ClientRequest('http://' . $this->socketServer->getAddress() . '/bar')
        );
        $body = yield $response->getBody()->buffer();

        $this->assertSame(Status::NOT_FOUND, $response->getStatus());
        $this->assertSame('Not Found', $body);
    }

    public function testHandlesErrorGracefully() {
        $router = $this->getRouter();
        $application = new HttpApplication($this->pluginManager, $router, $this->socketServer);
        $application->setLogger(new NullLogger());
        $this->registerRoutes($router);
        yield $application->execute();

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
        $router = $this->getRouter();
        $application = new HttpApplication($this->pluginManager, $router, $this->socketServer);
        $application->setLogger(new NullLogger());
        $application->setExceptionToResponseHandler(function(\Throwable $error) {
            return new ServerResponse(Status::SERVICE_UNAVAILABLE);
        });

        $this->registerRoutes($router);
        yield $application->execute();

        $url = 'http://' . $this->socketServer->getAddress() . '/throw-error';
        /** @var ClientResponse $response */
        $response = yield $this->client->request(new ClientRequest($url));

        $this->assertSame(Status::SERVICE_UNAVAILABLE, $response->getStatus());
    }

    public function testErrorLogged() {
        $router = $this->getRouter();
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
        $application = new HttpApplication($this->pluginManager, $router, $this->socketServer);
        $application->setLogger($logger);

        $this->registerRoutes($router);
        yield $application->execute();

        yield $this->client->request(new ClientRequest('http://' . $this->socketServer->getAddress() . '/throw-error'));
    }

    public function testAddingMiddlewareCanShortCircuitRouterMatching() {
        $router = $this->createMock(Router::class);
        $router->expects($this->never())->method('match');
        $logger = new NullLogger();
        $application = new HttpApplication($this->pluginManager, $router, $this->socketServer);
        $application->setLogger($logger);
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
        $application->addMiddleware($middleware);

        yield $application->execute();

        /** @var ClientResponse $response */
        $url = 'http://' . $this->socketServer->getAddress() . '/does_not_matter';
        $response = yield $this->client->request(new ClientRequest($url));
        $body = yield $response->getBody()->buffer();

        $this->assertSame(Status::ACCEPTED, $response->getStatus());
        $this->assertSame('Short circuited router', $body);
    }
}
