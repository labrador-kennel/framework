<?php

/**
 *
 * @license See LICENSE in source root
 * @version 1.0
 * @since   1.0
 */

namespace Cspray\Labrador\Http\Test\Unit\Router;

use Amp\Http\Server\Driver\Client;
use Amp\Http\Server\Request;
use Amp\Http\Server\Response;
use Amp\PHPUnit\AsyncTestCase;
use Cspray\Labrador\Http\Controller\Controller;
use Cspray\Labrador\Http\Exception\InvalidType;
use Cspray\Labrador\Http\HttpMethod;
use Cspray\Labrador\Http\Router\FastRouteRouter;
use Cspray\Labrador\Http\Router\RequestMapping;
use Cspray\Labrador\Http\Router\Route;
use Cspray\Labrador\Http\Router\RoutingResolutionReason;
use Cspray\Labrador\Http\Test\Unit\Stub\RequestDecoratorMiddleware;
use Cspray\Labrador\Http\Test\Unit\Stub\ResponseControllerStub;
use Cspray\Labrador\Http\Test\Unit\Stub\ResponseDecoratorMiddleware;
use Cspray\Labrador\Http\Test\Unit\Stub\ToStringControllerStub;
use FastRoute\DataGenerator\GroupCountBased as GcbDataGenerator;
use FastRoute\Dispatcher\GroupCountBased as GcbDispatcher;
use FastRoute\RouteCollector;
use FastRoute\RouteParser\Std as StdRouteParser;
use League\Uri\Http;

class FastRouteRouterTest extends AsyncTestCase {

    /**
     * @var Client
     */
    private $client;

    public function setUp() : void {
        parent::setUp();
        $this->client = $this->createMock(Client::class);
        $this->setTimeout(1500);
    }

    private function getRouter() : FastRouteRouter {
        return new FastRouteRouter(
            new RouteCollector(new StdRouteParser(), new GcbDataGenerator()),
            function($data) { return new GcbDispatcher($data); }
        );
    }

    private function getRequest(string $method, string $uri) : Request {
        return new Request($this->client, $method, Http::createFromString($uri));
    }

    public function testFastRouteDispatcherCallbackReturnsImproperTypeThrowsException() {
        $router = new FastRouteRouter(
            new RouteCollector(new StdRouteParser(), new GcbDataGenerator()),
            function() { return 'not a dispatcher'; }
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

        self::assertNull($resolution->controller);
        self::assertSame(RoutingResolutionReason::NotFound, $resolution->reason);
    }

    public function testRouterMethodNotAllowedReturnsCorrectController() {
        $router = $this->getRouter();
        $request = $this->getRequest('POST', 'http://labrador.dev/foo');
        $mock = $this->createMock(Controller::class);
        $router->addRoute(RequestMapping::fromMethodAndPath(HttpMethod::Get, '/foo'), $mock);
        $router->addRoute(RequestMapping::fromMethodAndPath(HttpMethod::Put, '/foo'), $mock);

        $resolution = $router->match($request);

        self::assertNull($resolution->controller);
        self::assertSame($resolution->reason, RoutingResolutionReason::MethodNotAllowed);
    }

    public function testRouterIsOkReturnsCorrectController() {
        $router = $this->getRouter();

        $request = $this->getRequest('GET', 'http://labrador.dev/foo');
        $router->addRoute(
            RequestMapping::fromMethodAndPath( HttpMethod::Get, '/foo'),
            $controller = $this->createMock(Controller::class)
        );

        $resolution = $router->match($request);

        self::assertSame(RoutingResolutionReason::RequestMatched, $resolution->reason);
        self::assertSame($controller, $resolution->controller);
    }

    public function testRouteWithParametersSetOnRequestAttributes() {
        $router = $this->getRouter();

        $request = $this->getRequest('POST', 'http://www.sprog.dev/foo/bar/qux');
        $router->addRoute(
            RequestMapping::fromMethodAndPath(HttpMethod::Post, '/foo/{name}/{id}'),
            $this->createMock(Controller::class)
        );

        $resolution = $router->match($request);

        self::assertSame(RoutingResolutionReason::RequestMatched, $resolution->reason);
        self::assertSame('bar', $request->getAttribute('name'));
        self::assertSame('qux', $request->getAttribute('id'));
    }

    public function testGetRoutesWithJustOne() {
        $router = $this->getRouter();
        $router->addRoute(
            RequestMapping::fromMethodAndPath(HttpMethod::Get, '/foo'),
            new ToStringControllerStub('foo_get')
        );

        $routes = $router->getRoutes();
        self::assertCount(1, $routes);
        self::assertInstanceOf(Route::class, $routes[0]);
        self::assertSame('/foo', $routes[0]->requestMapping->pathPattern);
        self::assertSame(HttpMethod::Get, $routes[0]->requestMapping->method);
        self::assertSame('foo_get', $routes[0]->controller->toString());
    }

    public function testGetRoutesWithOnePatternSupportingMultipleMethods() {
        $router = $this->getRouter();
        $router->addRoute(
            RequestMapping::fromMethodAndPath(HttpMethod::Get, '/foo/bar'),
            new ToStringControllerStub('foo_bar_get')
        );
        $router->addRoute(
            RequestMapping::fromMethodAndPath(HttpMethod::Post, '/foo/bar'),
            new ToStringControllerStub('foo_bar_post')
        );
        $router->addRoute(
            RequestMapping::fromMethodAndPath(HttpMethod::Put, '/foo/bar'),
            new ToStringControllerStub('foo_bar_put')
        );

        $expected = [
            [HttpMethod::Get, '/foo/bar', 'foo_bar_get'],
            [HttpMethod::Post, '/foo/bar', 'foo_bar_post'],
            [HttpMethod::Put, '/foo/bar', 'foo_bar_put']
        ];

        $actual = [];
        $routes = $router->getRoutes();
        foreach ($routes as $route) {
            self::assertInstanceOf(Route::class, $route);
            $actual[] = [$route->requestMapping->method, $route->requestMapping->pathPattern, $route->controller->toString()];
        }

        self::assertSame($expected, $actual);
    }

    public function testGetRoutesWithStaticAndVariable() {
        $router = $this->getRouter();
        $router->addRoute(
            RequestMapping::fromMethodAndPath(HttpMethod::Get, '/foo/bar/{id}'),
            new ToStringControllerStub('foo_bar_show_get')
        );
        $router->addRoute(
            RequestMapping::fromMethodAndPath(HttpMethod::Get, '/foo/baz/{name}'),
            new ToStringControllerStub('foo_bar_show_name_get')
        );
        $router->addRoute(
            RequestMapping::fromMethodAndPath(HttpMethod::Post, '/foo/baz'),
            new ToStringControllerStub('foo_baz_post')
        );
        $router->addRoute(
            RequestMapping::fromMethodAndPath(HttpMethod::Put, '/foo/quz'),
            new ToStringControllerStub('foo_quz_put')
        );

        $expected = [
            [HttpMethod::Get, '/foo/bar/{id}', 'foo_bar_show_get'],
            [HttpMethod::Get, '/foo/baz/{name}', 'foo_bar_show_name_get'],
            [HttpMethod::Post, '/foo/baz', 'foo_baz_post'],
            [HttpMethod::Put, '/foo/quz', 'foo_quz_put']
        ];
        $actual = [];
        $routes = $router->getRoutes();
        foreach ($routes as $route) {
            $this->assertInstanceOf(Route::class, $route);
            $actual[] = [$route->requestMapping->method, $route->requestMapping->pathPattern, $route->controller->toString()];
        }

        $this->assertSame($expected, $actual);
    }


    public function testUrlDecodingCustomAttributes() {
        $request = $this->getRequest('GET', 'http://example.com/foo%20bar');
        $router = $this->getRouter();
        $mock = $this->createMock(Controller::class);
        $router->addRoute(RequestMapping::fromMethodAndPath(HttpMethod::Get, '/{param}'), $mock);
        $router->match($request);

        $this->assertSame('foo bar', $request->getAttribute('param'));
    }

    private function defaultMiddlewares() {
        $requestDecorator = new RequestDecoratorMiddleware();
        $responseDecorator = new ResponseDecoratorMiddleware();

        return [$requestDecorator, $responseDecorator];
    }

    public function routerRouteMethodProvider() {
        $args = [];
        foreach (HttpMethod::cases() as $method) {
            $args[$method->value] = [$method];
        }
        return $args;
    }

    /**
     * @dataProvider routerRouteMethodProvider
     */
    public function testAddingMiddlewareToSingleRoute(HttpMethod $httpMethod) {
        $request = $this->getRequest($httpMethod->value, 'http://example.com/foo');
        $router = $this->getRouter();
        $responseController = new ResponseControllerStub(new Response(200, [], 'decorated value:'));
        $router->addRoute(
            RequestMapping::fromMethodAndPath($httpMethod, '/foo'),
            $responseController,
            ...$this->defaultMiddlewares()
        );

        $controller = $router->match($request)->controller;

        self::assertNotNull($controller);

        $response = $controller->handleRequest($request);
        $body = $response->getBody()->read();

        $this->assertSame('decorated value: foobar', $body);
    }
}
