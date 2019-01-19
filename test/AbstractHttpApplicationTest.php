<?php declare(strict_types=1);

namespace Cspray\Labrador\Http\Test;

use Amp\Artax\Client as HttpClient;
use Amp\Artax\DefaultClient as DefaultHttpClient;
use Amp\Http\Server\Response;
use function Amp\Socket\listen;
use Amp\Socket\Server as SocketServer;
use Amp\Socket\SocketException;
use Cspray\Labrador\Application;
use Cspray\Labrador\Http\AbstractHttpApplication;
use Cspray\Labrador\Http\Router\FastRouteRouter;
use Cspray\Labrador\Http\Router\Router;
use Cspray\Labrador\Http\StatusCodes;
use Cspray\Labrador\Http\Test\Stub\ErrorThrowingController;
use Cspray\Labrador\Http\Test\Stub\ResponseControllerStub;
use FastRoute\DataGenerator\GroupCountBased as GcbDataGenerator;
use FastRoute\Dispatcher\GroupCountBased as GcbDispatcher;
use FastRoute\RouteCollector;
use FastRoute\RouteParser\Std as StdRouteParser;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

class HttpApplicationTestSubject extends AbstractHttpApplication {

    private $serverSocket;

    public function __construct(LoggerInterface $logger, Router $router, SocketServer $serverSocket) {
        parent::__construct($logger, $router);
        $this->serverSocket = $serverSocket;
    }

    /**
     * @return Server[]
     * @throws SocketException
     */
    protected function getSocketServers(): array {
        return [$this->serverSocket];
    }
}

class ErrorRespondingApplicationTestSubject extends AbstractHttpApplication {

    private $serverSocket;

    public function __construct(LoggerInterface $logger, Router $router, SocketServer $serverSocket) {
        parent::__construct($logger, $router);
        $this->serverSocket = $serverSocket;
    }

    /**
     * @return Server[]
     * @throws SocketException
     */
    protected function getSocketServers(): array {
        return [$this->serverSocket];
    }

    protected function exceptionToResponse(\Throwable $throwable): Response {
        return new Response(StatusCodes::SERVICE_UNAVAILABLE);
    }
}

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
        $application = new HttpApplicationTestSubject(new NullLogger(), $router, $this->socketServer);
        $this->registerRoutes($router);

        yield $application->execute();

        /** @var Response $response */
        $response = yield $this->client->request('http://' . $this->socketServer->getAddress() . '/foo');
        $body = yield $response->getBody();

        $this->assertSame(StatusCodes::OK, $response->getStatus());
        $this->assertSame('From controller', $body);
    }

    public function testRouteNotFound() {
        $router = $this->getRouter();
        $application = new HttpApplicationTestSubject(new NullLogger(), $router, $this->socketServer);
        $this->registerRoutes($router);
        yield $application->execute();

        /** @var Response $response */
        $response = yield $this->client->request('http://' . $this->socketServer->getAddress() . '/bar');
        $body = yield $response->getBody();

        $this->assertSame(StatusCodes::NOT_FOUND, $response->getStatus());
        $this->assertSame('Not Found', $body);
    }

    public function testHandlesErrorGracefully() {
        $router = $this->getRouter();
        $application = new HttpApplicationTestSubject(new NullLogger(), $router, $this->socketServer);
        $this->registerRoutes($router);
        yield $application->execute();

        $response = yield $this->client->request('http://' . $this->socketServer->getAddress() . '/throw-error');

        $this->assertSame(StatusCodes::INTERNAL_SERVER_ERROR, $response->getStatus());

        // now make sure the server hasn't crashed
        $response = yield $this->client->request('http://' . $this->socketServer->getAddress() . '/foo');
        $body = yield $response->getBody();

        $this->assertSame(StatusCodes::OK, $response->getStatus());
        $this->assertSame('From controller', $body);
    }

    public function testErrorResponseReturnedFromApplication() {
        $router = $this->getRouter();
        $application = new ErrorRespondingApplicationTestSubject(new NullLogger(), $router, $this->socketServer);
        $this->registerRoutes($router);
        yield $application->execute();

        $response = yield $this->client->request('http://' . $this->socketServer->getAddress() . '/throw-error');

        $this->assertSame(StatusCodes::SERVICE_UNAVAILABLE, $response->getStatus());
    }


}