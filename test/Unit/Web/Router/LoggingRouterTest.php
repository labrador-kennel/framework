<?php declare(strict_types=1);

namespace Labrador\Test\Unit\Web\Router;

use Amp\Http\Server\Driver\Client;
use Amp\Http\Server\Middleware;
use Amp\Http\Server\Request;
use Amp\Http\Server\Response;
use FastRoute\DataGenerator\GroupCountBased as GcbDataGenerator;
use FastRoute\Dispatcher\GroupCountBased as GcbDispatcher;
use FastRoute\RouteCollector;
use FastRoute\RouteParser\Std as StdRouteParser;
use Labrador\Test\Unit\Web\Stub\BarMiddleware;
use Labrador\Test\Unit\Web\Stub\FooMiddleware;
use Labrador\Test\Unit\Web\Stub\ResponseRequestHandlerStub;
use Labrador\Web\HttpMethod;
use Labrador\Web\Router\FastRouteRouter;
use Labrador\Web\Router\LoggingRouter;
use Labrador\Web\Router\Mapping\GetMapping;
use Labrador\Web\Router\Mapping\PostMapping;
use Labrador\Web\Router\Mapping\RequestMapping;
use Labrador\Web\Router\Route;
use Labrador\Web\Router\Router;
use Labrador\Web\Router\RoutingResolution;
use Labrador\Web\Router\RoutingResolutionReason;
use League\Uri\Http;
use Monolog\Handler\TestHandler;
use Monolog\Logger;
use Monolog\Processor\PsrLogMessageProcessor;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class LoggingRouterTest extends TestCase {

    private Router&MockObject $mockRouter;

    private FastRouteRouter $router;

    private TestHandler $handler;

    private Client&MockObject $client;

    private Logger $logger;

    protected function setUp() : void {
        $this->client = $this->getMockBuilder(Client::class)->getMock();
        $this->handler = new TestHandler();
        $this->mockRouter = $this->getMockBuilder(Router::class)->getMock();
        $this->router = new FastRouteRouter(
            new RouteCollector(new StdRouteParser(), new GcbDataGenerator()),
            function(array $data) : GcbDispatcher { return new GcbDispatcher($data);
            }
        );
        $this->logger = new Logger(
            'logging-router-test',
            [$this->handler],
            [new PsrLogMessageProcessor()]
        );
    }

    public function testAddRouteDelegatedToPassedRouter() : void {
        $requestHandler = new ResponseRequestHandlerStub(new Response());
        $mapping = new GetMapping('/');
        $this->mockRouter->expects($this->once())
            ->method('addRoute')
            ->with(
                $this->callback(function (RequestMapping $mapping) : bool {
                    return $mapping instanceof GetMapping && $mapping->getPath() === '/';
                }),
                $this->isInstanceOf(ResponseRequestHandlerStub::class),
            )->willReturn(
                new Route($mapping, $requestHandler, [])
            );

        $subject = new LoggingRouter($this->mockRouter, $this->logger);
        $subject->addRoute($mapping, $requestHandler);
    }

    public function testAddRouteWithMiddlewareDelegatedToPassedRouter() : void {
        $middleware = $this->getMockBuilder(Middleware::class)->getMock();
        $requestHandler = new ResponseRequestHandlerStub(new Response());
        $mapping = new GetMapping('/');
        $this->mockRouter->expects($this->once())
            ->method('addRoute')
            ->with(
                $this->callback(function (RequestMapping $mapping) : bool {
                    return $mapping instanceof GetMapping && $mapping->getPath() === '/';
                }),
                $this->isInstanceOf(ResponseRequestHandlerStub::class),
                $middleware
            )->willReturn(
                new Route($mapping, $requestHandler, [])
            );

        $subject = new LoggingRouter($this->mockRouter, $this->logger);
        $subject->addRoute($mapping, $requestHandler, $middleware);
    }

    public function testMatchDelegatedToPassedRouter() : void {
        $requestHandler = new ResponseRequestHandlerStub(new Response());
        $request = new Request(
            $this->client,
            HttpMethod::Post->value,
            Http::new('https://example.com/router')
        );

        $this->mockRouter->expects($this->once())
            ->method('match')
            ->with($request)
            ->willReturn(
                $reason = new RoutingResolution($requestHandler, [], RoutingResolutionReason::NotFound)
            );

        $subject = new LoggingRouter($this->mockRouter, $this->logger);
        $actual = $subject->match($request);

        self::assertSame($reason, $actual);
    }

    public function testGetRoutesDelegatedToPassedRouter() : void {
        $subject = new LoggingRouter($this->mockRouter, $this->logger);

        $this->mockRouter->expects($this->once())
            ->method('getRoutes')
            ->willReturn([]);

        $subject->getRoutes();
    }

    public function testAddingRouteWithNoMiddlewareLogsPertinentInformation() : void {
        $requestHandler = new ResponseRequestHandlerStub(new Response());

        $subject = new LoggingRouter($this->router, $this->logger);
        $subject->addRoute(
            new GetMapping('/'),
            $requestHandler
        );

        self::assertTrue($this->handler->hasInfo([
            'message' => 'Routing "GET /" to ' . ResponseRequestHandlerStub::class . '.',
            'context' => [
                'method' => 'GET',
                'path' => '/',
                'request_handler' => ResponseRequestHandlerStub::class,
                'middleware' => []
            ]
        ]));
    }

    public function testAddingRouteWithMiddlewareLogsPertinentInformation() : void {
        $requestHandler = new ResponseRequestHandlerStub(new Response());
        $middlewares = [new FooMiddleware(), new BarMiddleware()];

        $subject = new LoggingRouter($this->router, $this->logger);
        $subject->addRoute(
            new GetMapping('/hello/world'),
            $requestHandler,
            ...$middlewares
        );

        self::assertTrue($this->handler->hasInfo([
            'message' => sprintf(
                'Routing "GET /hello/world" to %s with middleware %s, %s.',
                ResponseRequestHandlerStub::class,
                FooMiddleware::class,
                BarMiddleware::class
            ),
            'context' => [
                'method' => 'GET',
                'path' => '/hello/world',
                'request_handler' => ResponseRequestHandlerStub::class,
                'middleware' => [
                    FooMiddleware::class,
                    BarMiddleware::class
                ]
            ]
        ]));
    }

    public function testMatchReturnsRoutingResolutionRequestMatchedLogsPertinentOutput() : void {
        $requestHandler = new ResponseRequestHandlerStub(new Response());

        $subject = new LoggingRouter($this->router, $this->logger);
        $subject->addRoute(new GetMapping('/foo/bar'), $requestHandler);

        $request = new Request(
            $this->client,
            HttpMethod::Get->value,
            Http::new('https://example.com/foo/bar')
        );

        $resolution = $subject->match($request);

        self::assertSame(RoutingResolutionReason::RequestMatched, $resolution->reason);

        self::assertTrue($this->handler->hasInfo([
            'message' => 'Routed "GET /foo/bar" to ' . ResponseRequestHandlerStub::class . '.',
            [
                'method' => 'GET',
                'path' => '/foo/bar',
                'request_handler' => ResponseRequestHandlerStub::class
            ]
        ]));
    }

    public function testMatchReturnsRoutingResolutionNotFoundLogsPertinentOutput() : void {
        $subject = new LoggingRouter($this->router, $this->logger);

        $request = new Request(
            $this->client,
            HttpMethod::Get->value,
            Http::new('https://example.com/foo/bar')
        );

        $resolution = $subject->match($request);

        self::assertSame(RoutingResolutionReason::NotFound, $resolution->reason);

        self::assertTrue($this->handler->hasNotice([
            'message' => 'Failed routing "GET /foo/bar" to a request handler because no route was found.',
            [
                'method' => 'GET',
                'path' => '/foo/bar',
            ]
        ]));
    }

    public function testMatchReturnsRoutingResolutionMethodNotAllowedLogsPertinentOutput() : void {
        $requestHandler = new ResponseRequestHandlerStub(new Response());
        $subject = new LoggingRouter($this->router, $this->logger);
        $subject->addRoute(new PostMapping('/foo/bar'), $requestHandler);

        $request = new Request(
            $this->client,
            HttpMethod::Get->value,
            Http::new('https://example.com/foo/bar')
        );

        $resolution = $subject->match($request);

        self::assertSame(RoutingResolutionReason::MethodNotAllowed, $resolution->reason);

        self::assertTrue($this->handler->hasNotice([
            'message' => 'Failed routing "GET /foo/bar" to a request handler because route does not allow requested method.',
            [
                'method' => 'GET',
                'path' => '/foo/bar',
            ]
        ]));
    }
}
