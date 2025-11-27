<?php

/**
 *
 * @license See LICENSE in source root
 * @version 1.0
 * @since   1.0
 */

namespace Labrador\Test\Unit\Web\Router;

use Amp\Http\HttpStatus;
use Amp\Http\Server\Driver\Client;
use Amp\Http\Server\Request;
use Amp\Http\Server\RequestHandler;
use Amp\Http\Server\Response;
use FastRoute\DataGenerator\GroupCountBased as GcbDataGenerator;
use FastRoute\Dispatcher\GroupCountBased as GcbDispatcher;
use FastRoute\RouteCollector;
use FastRoute\RouteParser\Std as StdRouteParser;
use Labrador\Test\Unit\Web\Stub\FooBarGetRequestHandlerStub;
use Labrador\Test\Unit\Web\Stub\FooBarPostRequestHandlerStub;
use Labrador\Test\Unit\Web\Stub\FooBarPutRequestHandlerStub;
use Labrador\Test\Unit\Web\Stub\FooBarShowGetRequestHandlerStub;
use Labrador\Test\Unit\Web\Stub\FooBarShowNameGetRequestHandlerStub;
use Labrador\Test\Unit\Web\Stub\FooBazPostRequestHandlerStub;
use Labrador\Test\Unit\Web\Stub\FooGetRequestHandlerStub;
use Labrador\Test\Unit\Web\Stub\FooQuzPostRequestHandlerStub;
use Labrador\Test\Unit\Web\Stub\RequestDecoratorMiddleware;
use Labrador\Test\Unit\Web\Stub\ResponseRequestHandlerStub;
use Labrador\Test\Unit\Web\Stub\ResponseDecoratorMiddleware;
use Labrador\Web\Exception\InvalidType;
use Labrador\Web\HttpMethod;
use Labrador\Web\Router\FastRouteRouter;
use Labrador\Web\Router\Mapping\GetMapping;
use Labrador\Web\Router\Mapping\PostMapping;
use Labrador\Web\Router\Mapping\PutMapping;
use Labrador\Web\Router\Mapping\RequestMapping;
use Labrador\Web\Router\Route;
use Labrador\Web\Router\RoutingResolutionReason;
use League\Uri\Http;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use function Amp\Http\Server\Middleware\stackMiddleware;

class FastRouteRouterTest extends TestCase {

    /**
     * @var Client
     */
    private $client;

    public function setUp() : void {
        parent::setUp();
        $this->client = $this->createMock(Client::class);
    }

    private function getRouter() : FastRouteRouter {
        return new FastRouteRouter(
            new RouteCollector(new StdRouteParser(), new GcbDataGenerator()),
            function($data) { return new GcbDispatcher($data);
            }
        );
    }

    private function getRequest(string $method, string $uri) : Request {
        return new Request($this->client, $method, Http::new($uri));
    }

    public function testFastRouteDispatcherCallbackReturnsImproperTypeThrowsException() {
        $router = new FastRouteRouter(
            new RouteCollector(new StdRouteParser(), new GcbDataGenerator()),
            function() { return 'not a dispatcher';
            }
        );

        $this->expectException(InvalidType::class);
        $expectedMsg = 'A FastRoute\\Dispatcher must be returned from dispatcher callback injected in constructor';
        $this->expectExceptionMessage($expectedMsg);

        $router->match($this->getRequest('GET', '/'));
    }

    public function testRouterNotFoundReturnsCorrectResolution() {
        $router = $this->getRouter();
        $request = $this->getRequest('GET', '/');
        $resolution = $router->match($request);

        self::assertNull($resolution->requestHandler);
        self::assertSame(RoutingResolutionReason::NotFound, $resolution->reason);
    }

    public function testRouterMethodNotAllowedReturnsCorrectRequestHandler() {
        $router = $this->getRouter();
        $request = $this->getRequest('POST', 'http://labrador.dev/foo');
        $mock = $this->createMock(RequestHandler::class);
        $router->addRoute(new GetMapping('/foo'), $mock);
        $router->addRoute(new PutMapping('/foo'), $mock);

        $resolution = $router->match($request);

        self::assertNull($resolution->requestHandler);
        self::assertSame($resolution->reason, RoutingResolutionReason::MethodNotAllowed);
    }

    public function testRouterIsOkReturnsCorrectRequestHandler() {
        $router = $this->getRouter();

        $request = $this->getRequest('GET', 'http://labrador.dev/foo');
        $router->addRoute(
            new GetMapping('/foo'),
            $requestHandler = $this->createMock(RequestHandler::class)
        );

        $resolution = $router->match($request);

        self::assertSame(RoutingResolutionReason::RequestMatched, $resolution->reason);
        self::assertSame($requestHandler, $resolution->requestHandler);
    }

    public function testRouteWithParametersSetOnRequestAttributes() {
        $router = $this->getRouter();

        $request = $this->getRequest('POST', 'http://www.sprog.dev/foo/bar/qux');
        $router->addRoute(
            new PostMapping('/foo/{name}/{id}'),
            $this->createMock(RequestHandler::class)
        );

        $resolution = $router->match($request);

        self::assertSame(RoutingResolutionReason::RequestMatched, $resolution->reason);
        self::assertSame('bar', $request->getAttribute('name'));
        self::assertSame('qux', $request->getAttribute('id'));
    }

    public function testGetRoutesWithJustOne() {
        $router = $this->getRouter();
        $router->addRoute(
            new GetMapping('/foo'),
            new FooGetRequestHandlerStub()
        );

        $routes = $router->getRoutes();
        self::assertCount(1, $routes);
        self::assertInstanceOf(Route::class, $routes[0]);
        self::assertSame('/foo', $routes[0]->requestMapping->getPath());
        self::assertSame(HttpMethod::Get, $routes[0]->requestMapping->getHttpMethod());
        self::assertSame(FooGetRequestHandlerStub::class, $routes[0]->requestHandler::class);
    }

    public function testGetRoutesWithOnePatternSupportingMultipleMethods() {
        $router = $this->getRouter();
        $router->addRoute(
            new GetMapping('/foo/bar'),
            new FooBarGetRequestHandlerStub()
        );
        $router->addRoute(
            new PostMapping('/foo/bar'),
            new FooBarPostRequestHandlerStub()
        );
        $router->addRoute(
            new PutMapping('/foo/bar'),
            new FooBarPutRequestHandlerStub()
        );

        $expected = [
            [HttpMethod::Get, '/foo/bar', FooBarGetRequestHandlerStub::class],
            [HttpMethod::Post, '/foo/bar', FooBarPostRequestHandlerStub::class],
            [HttpMethod::Put, '/foo/bar', FooBarPutRequestHandlerStub::class]
        ];

        $actual = [];
        $routes = $router->getRoutes();
        foreach ($routes as $route) {
            self::assertInstanceOf(Route::class, $route);
            $actual[] = [$route->requestMapping->getHttpMethod(), $route->requestMapping->getPath(), $route->requestHandler::class];
        }

        self::assertSame($expected, $actual);
    }

    public function testGetRoutesWithStaticAndVariable() {
        $router = $this->getRouter();
        $router->addRoute(
            new GetMapping('/foo/bar/{id}'),
            new FooBarShowGetRequestHandlerStub()
        );
        $router->addRoute(
            new GetMapping('/foo/baz/{name}'),
            new FooBarShowNameGetRequestHandlerStub()
        );
        $router->addRoute(
            new PostMapping('/foo/baz'),
            new FooBazPostRequestHandlerStub()
        );
        $router->addRoute(
            new PutMapping('/foo/quz'),
            new FooQuzPostRequestHandlerStub()
        );

        $expected = [
            [HttpMethod::Get, '/foo/bar/{id}', FooBarShowGetRequestHandlerStub::class],
            [HttpMethod::Get, '/foo/baz/{name}', FooBarShowNameGetRequestHandlerStub::class],
            [HttpMethod::Post, '/foo/baz', FooBazPostRequestHandlerStub::class],
            [HttpMethod::Put, '/foo/quz', FooQuzPostRequestHandlerStub::class]
        ];
        $actual = [];
        $routes = $router->getRoutes();
        foreach ($routes as $route) {
            $this->assertInstanceOf(Route::class, $route);
            $actual[] = [$route->requestMapping->getHttpMethod(), $route->requestMapping->getPath(), $route->requestHandler::class];
        }

        $this->assertSame($expected, $actual);
    }


    public function testUrlDecodingCustomAttributes() {
        $request = $this->getRequest('GET', 'http://example.com/foo%20bar');
        $router = $this->getRouter();
        $mock = $this->createMock(RequestHandler::class);
        $router->addRoute(new GetMapping('/{param}'), $mock);
        $router->match($request);

        $this->assertSame('foo bar', $request->getAttribute('param'));
    }

    private function defaultMiddlewares() {
        $requestDecorator = new RequestDecoratorMiddleware();
        $responseDecorator = new ResponseDecoratorMiddleware();

        return [$requestDecorator, $responseDecorator];
    }

    public static function routerRouteMethodProvider() {
        $args = [];
        foreach (HttpMethod::cases() as $method) {
            $mappingClass = 'Labrador\\Web\\Router\\Mapping\\' . $method->name . 'Mapping';
            $args[$method->value] = [new $mappingClass('/foo')];
        }
        return $args;
    }

    #[DataProvider('routerRouteMethodProvider')]
    public function testAddingMiddlewareToSingleRoute(RequestMapping $requestMapping) {
        $request = $this->getRequest($requestMapping->getHttpMethod()->value, 'http://example.com' . $requestMapping->getPath());
        $router = $this->getRouter();
        $responseRequestHandler = new ResponseRequestHandlerStub(new Response(200, [], 'decorated value:'));
        $middlewares = $this->defaultMiddlewares();
        $router->addRoute(
            $requestMapping,
            $responseRequestHandler,
            ...$middlewares
        );

        $route = $router->match($request);

        self::assertSame($responseRequestHandler, $route->requestHandler);
        self::assertSame($middlewares, $route->middleware);

        $handler = stackMiddleware($route->requestHandler, ...$route->middleware);

        $response = $handler->handleRequest($request);
        $body = $response->getBody()->read();

        $this->assertSame('decorated value: foobar', $body);
    }

    public function testTrailingSlashWithMatchedPathDoesNotResultIn404() : void {
        $request = $this->getRequest(HttpMethod::Get->value, 'http://example.com/found-request-handler/');
        $router = $this->getRouter();
        $responseRequestHandler = new ResponseRequestHandlerStub(new Response(200, [], 'found request handler with trailing slash'));
        $router->addRoute(
            new GetMapping('/found-request-handler'),
            $responseRequestHandler,
        );

        $requestHandler = $router->match($request)->requestHandler;

        self::assertNotNull($requestHandler);

        $response = $requestHandler->handleRequest($request);


        self::assertSame(HttpStatus::OK, $response->getStatus());
        self::assertSame('found request handler with trailing slash', $response->getBody()->read());
    }

    public function testTrailingSlashInAddedRouteDoesNotResultIn404() : void {
        $request = $this->getRequest(HttpMethod::Get->value, 'http://example.com/found-request-handler');
        $router = $this->getRouter();
        $responseRequestHandler = new ResponseRequestHandlerStub(new Response(200, [], 'found request handler with trailing slash'));
        $router->addRoute(
            new GetMapping('/found-request-handler/'),
            $responseRequestHandler,
        );

        $requestHandler = $router->match($request)->requestHandler;

        self::assertNotNull($requestHandler);

        $response = $requestHandler->handleRequest($request);


        self::assertSame(HttpStatus::OK, $response->getStatus());
        self::assertSame('found request handler with trailing slash', $response->getBody()->read());
    }

    public function testFoundRootRequestHandlerJustSlash() : void {
        $request = $this->getRequest(HttpMethod::Get->value, 'http://example.com/');
        $router = $this->getRouter();
        $responseRequestHandler = new ResponseRequestHandlerStub(new Response(200, [], 'found request handler with just slash'));
        $router->addRoute(
            new GetMapping('/'),
            $responseRequestHandler,
        );

        $requestHandler = $router->match($request)->requestHandler;

        self::assertNotNull($requestHandler);

        $response = $requestHandler->handleRequest($request);

        self::assertSame(HttpStatus::OK, $response->getStatus());
        self::assertSame('found request handler with just slash', $response->getBody()->read());
    }
}
