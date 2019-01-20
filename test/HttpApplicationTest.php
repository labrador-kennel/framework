<?php declare(strict_types=1);

namespace Cspray\Labrador\Http\Test;

use Amp\Artax\Client as HttpClient;
use Amp\Artax\DefaultClient as DefaultHttpClient;
use Amp\Http\Server\Middleware;
use Amp\Http\Server\Request;
use Amp\Http\Server\RequestHandler;
use Amp\Http\Server\Response;
use Amp\Http\Status;
use Amp\Promise;
use function Amp\Socket\listen;
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
use Monolog\Logger;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

class AbstractHttpApplicationTest extends AsyncTestCase {

    /**
     * @var SocketServer
     */
    private $socketServer;

    /**
     * @var HttpClient
     */
    private $client;

    /**
     * @throws SocketException
     */
    public function setUp() {
        parent::setUp();
        $this->timeout(1500);
        $this->socketServer = listen('tcp://127.0.0.1:0');
        $this->client = new DefaultHttpClient();
    }

    public function tearDown() {
        parent::tearDown();
        $this->socketServer->close();
    }

    private function getRouter() {
        return new FastRouteRouter(
            new RouteCollector(new StdRouteParser(), new GcbDataGenerator()),
            function($data) { return new GcbDispatcher($data); }
        );
    }

    private function registerRoutes(Router $router) {
        $controller = new ResponseControllerStub(new Response(200, [], 'From controller'));
        $errorController = new ErrorThrowingController();
        $router->addRoute('GET', '/foo', $controller);
        $router->addRoute('GET', '/throw-error', $errorController);
    }

    public function testBasicRouteFound() {
        $router = $this->getRouter();
        $application = new HttpApplication(new NullLogger(), $router, $this->socketServer);
        $this->registerRoutes($router);

        yield $application->execute();

        /** @var Response $response */
        $response = yield $this->client->request('http://' . $this->socketServer->getAddress() . '/foo');
        $body = yield $response->getBody();

        $this->assertSame(Status::OK, $response->getStatus());
        $this->assertSame('From controller', $body);
    }

    public function testRouteNotFound() {
        $router = $this->getRouter();
        $application = new HttpApplication(new NullLogger(), $router, $this->socketServer);
        $this->registerRoutes($router);
        yield $application->execute();

        /** @var Response $response */
        $response = yield $this->client->request('http://' . $this->socketServer->getAddress() . '/bar');
        $body = yield $response->getBody();

        $this->assertSame(Status::NOT_FOUND, $response->getStatus());
        $this->assertSame('Not Found', $body);
    }

    public function testHandlesErrorGracefully() {
        $router = $this->getRouter();
        $application = new HttpApplication(new NullLogger(), $router, $this->socketServer);
        $this->registerRoutes($router);
        yield $application->execute();

        $response = yield $this->client->request('http://' . $this->socketServer->getAddress() . '/throw-error');

        $this->assertSame(Status::INTERNAL_SERVER_ERROR, $response->getStatus());

        // now make sure the server hasn't crashed
        $response = yield $this->client->request('http://' . $this->socketServer->getAddress() . '/foo');
        $body = yield $response->getBody();

        $this->assertSame(Status::OK, $response->getStatus());
        $this->assertSame('From controller', $body);
    }

    public function testErrorResponseReturnedFromApplication() {
        $router = $this->getRouter();
        $application = new HttpApplication(new NullLogger(), $router, $this->socketServer);
        $application->setExceptionToResponseHandler(function(\Throwable $error) {
            return new Response(Status::SERVICE_UNAVAILABLE);
        });

        $this->registerRoutes($router);
        yield $application->execute();

        $response = yield $this->client->request('http://' . $this->socketServer->getAddress() . '/throw-error');

        $this->assertSame(Status::SERVICE_UNAVAILABLE, $response->getStatus());
    }

    public function testErrorLogged() {
        $router = $this->getRouter();
        $logger = $this->createMock(LoggerInterface::class);
        $expectedMsg = 'Exception thrown processing GET http://' . $this->socketServer->getAddress() . '/throw-error. Message: Controller thrown exception';
        $logger->expects($this->once())
               ->method('critical')
               ->with($expectedMsg, $this->callback(function($secondArg) {
                   $exception = $secondArg['exception'] ?? null;
                   return $exception instanceof \Exception && $exception->getMessage() === 'Controller thrown exception';
               }));
        $application = new HttpApplication($logger, $router, $this->socketServer);

        $this->registerRoutes($router);
        yield $application->execute();

        yield $this->client->request('http://' . $this->socketServer->getAddress() . '/throw-error');
    }

    public function testAddingMiddlewareCanShortCircuitRouterMatching() {
        $router = $this->createMock(Router::class);
        $router->expects($this->never())->method('match');
        $logger = new NullLogger();
        $application = new HttpApplication($logger, $router, $this->socketServer);
        $middleware = new class implements Middleware {

            /**
             * @param Request $request
             * @param RequestHandler $requestHandler
             *
             * @return Promise<\Amp\Http\Server\Response>
             */
            public function handleRequest(Request $request, RequestHandler $requestHandler): Promise {
                $response = new Response(Status::ACCEPTED, [], 'Short circuited router');
                return new Success($response);
            }
        };
        $application->addMiddleware($middleware);
        /** @var Response $response */
        yield $application->execute();

        /** @var \Amp\Artax\Response $response */
        $response = yield $this->client->request('http://' . $this->socketServer->getAddress() . '/does_not_matter');
        $body = yield $response->getBody()->read();

        $this->assertSame(Status::ACCEPTED, $response->getStatus());
        $this->assertSame('Short circuited router', $body);
    }

}