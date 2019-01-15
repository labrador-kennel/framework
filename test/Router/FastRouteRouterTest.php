<?php

/**
 *
 * @license See LICENSE in source root
 * @version 1.0
 * @since   1.0
 */

namespace Cspray\Labrador\Http\Test\Router;

use Amp\Http\Server\Driver\Client;
use Amp\Success;
use Cspray\Labrador\Http\Controller\Controller;
use Cspray\Labrador\Http\Router\FastRouteRouter;
use Cspray\Labrador\Http\Router\Route;
use Cspray\Labrador\Http\Exception\InvalidHandlerException;
use Cspray\Labrador\Http\Exception\InvalidTypeException;
use Cspray\Labrador\Http\StatusCodes;
use Cspray\Labrador\Http\Test\AsyncTestCase;
use FastRoute\DataGenerator\GroupCountBased as GcbDataGenerator;
use FastRoute\Dispatcher\GroupCountBased as GcbDispatcher;
use FastRoute\RouteCollector;
use FastRoute\RouteParser\Std as StdRouteParser;
use Amp\Http\Server\Request;
use Amp\Http\Server\Response;
use League\Uri\Http;

class FastRouteRouterTest extends AsyncTestCase {

    private $mockResolver;
    /**
     * @var Client
     */
    private $client;

    public function setUp() {
        parent::setUp();
        $this->client = $this->createMock(Client::class);
        $this->timeout(1500);
    }

    private function getRouter() {
        return new FastRouteRouter(
            new RouteCollector(new StdRouteParser(), new GcbDataGenerator()),
            function($data) { return new GcbDispatcher($data); }
        );
    }

    private function getRequest(string $method, string $uri) : Request {
        return new Request($this->client, $method, Http::createFromString($uri));
    }

    private function getMockController(Request $request, Response $response) {
        $controller = $this->createMock(Controller::class);
        $controller->expects($this->once())
            ->method('handleRequest')
            ->with($request)
            ->willReturn(new Success($response));
        return $controller;
    }

    /**
     * @throws InvalidHandlerException
     */
    public function testFastRouteDispatcherCallbackReturnsImproperTypeThrowsException() {
        $router = new FastRouteRouter(
            new RouteCollector(new StdRouteParser(), new GcbDataGenerator()),
            function() { return 'not a dispatcher'; }
        );

        $this->expectException(InvalidTypeException::class);
        $this->expectExceptionMessage('A FastRoute\\Dispatcher must be returned from dispatcher callback injected in constructor');

        $router->match($this->getRequest('GET', '/'));
    }

    /**
     * @throws InvalidHandlerException
     */
    public function testRouterNotFoundReturnsCorrectController() {
        $router = $this->getRouter();
        $request = $this->getRequest('GET', Http::createFromString('/'));
        $controller = $router->match($request);
        /** @var Response $response */
        $response = yield $controller->handleRequest($request);
        $body = yield $response->getBody()->read();
        $this->assertSame(StatusCodes::NOT_FOUND, $response->getStatus());
        $this->assertSame('Not Found', $body);
    }

    public function testRouterMethodNotAllowedReturnsCorrectController() {
        $router = $this->getRouter();
        $request = $this->getRequest('POST', Http::createFromString('http://labrador.dev/foo'));
        $mock = $this->createMock(Controller::class);
        $router->get('/foo', $mock);
        $router->put('/foo', $mock);

        $controller = $router->match($request);
        /** @var Response $response */
        $response = yield $controller->handleRequest($request);
        $body = yield $response->getBody()->read();
        $this->assertSame(StatusCodes::METHOD_NOT_ALLOWED, $response->getStatus());
        $this->assertSame('Method Not Allowed', $body);
    }

    public function testRouterIsOkReturnsCorrectController() {
        $router = $this->getRouter();

        $request = $this->getRequest('GET', 'http://labrador.dev/foo');
        $mock = $this->getMockController($request, new Response(200, [], 'test val'));
        $router->get('/foo', $mock);

        $controller = $router->match($request);
        /** @var Response $response */
        $response = yield $controller->handleRequest($request);
        $body = yield $response->getBody()->read();

        $this->assertSame(StatusCodes::OK, $response->getStatus());
        $this->assertSame('test val', $body);
    }

    public function testRouteWithParametersSetOnRequestAttributes() {
        $router = $this->getRouter();

        $request = $this->getRequest('POST', 'http://www.sprog.dev/foo/bar/qux');
        $mock = $this->createMock(Controller::class);
        $router->post('/foo/{name}/{id}', $mock);

        $router->match($request);

        $this->assertSame('bar', $request->getAttribute('name'));
        $this->assertSame('qux', $request->getAttribute('id'));
    }

    public function testGetRoutesWithJustOne() {
        $router = $this->getRouter();
        $mock = $this->createMock(Controller::class);
        $router->get('/foo', $mock);

        $routes = $router->getRoutes();
        $this->assertCount(1, $routes);
        $this->assertInstanceOf(Route::class, $routes[0]);
        $this->assertSame('/foo', $routes[0]->getPattern());
        $this->assertSame('GET', $routes[0]->getMethod());
        $this->assertSame(get_class($mock), $routes[0]->getControllerClass());
    }

    public function testGetRoutesWithOnePatternSupportingMultipleMethods() {
        $router = $this->getRouter();
        $mock = $this->createMock(Controller::class);
        $router->get('/foo/bar', $mock);
        $router->post('/foo/bar', $mock);
        $router->put('/foo/bar', $mock);

        $expected = [
            ['GET', '/foo/bar', get_class($mock)],
            ['POST', '/foo/bar', get_class($mock)],
            ['PUT', '/foo/bar', get_class($mock)]
        ];
        $actual = [];
        $routes = $router->getRoutes();
        foreach ($routes as $route) {
            $this->assertInstanceOf(Route::class, $route);
            $actual[] = [$route->getMethod(), $route->getPattern(), $route->getControllerClass()];
        }

        $this->assertSame($expected, $actual);
    }

    public function testGetRoutesWithStaticAndVariable() {
        $router = $this->getRouter();
        $mock = $this->createMock(Controller::class);
        $router->get('/foo/bar/{id}', $mock);
        $router->get('/foo/baz/{name}', $mock);
        $router->post('/foo/baz', $mock);
        $router->put('/foo/quz', $mock);

        $expected = [
            ['GET', '/foo/bar/{id}', get_class($mock)],
            ['GET', '/foo/baz/{name}', get_class($mock)],
            ['POST', '/foo/baz', get_class($mock)],
            ['PUT', '/foo/quz', get_class($mock)]
        ];
        $actual = [];
        $routes = $router->getRoutes();
        foreach ($routes as $route) {
            $this->assertInstanceOf(Route::class, $route);
            $actual[] = [$route->getMethod(), $route->getPattern(), $route->getControllerClass()];
        }

        $this->assertSame($expected, $actual);
    }

    public function testMountingRouterAddsPrefix() {
        $router = $this->getRouter();
        $mock = $this->createMock(Controller::class);
        $router->mount('/prefix', function(FastRouteRouter $router) use($mock) {
            $router->get('/foo', $mock);
        });
        $router->get('/noprefix', $mock);

        $expected = [
            ['GET', '/prefix/foo', get_class($mock)],
            ['GET', '/noprefix', get_class($mock)]
        ];
        $actual = [];
        $routes = $router->getRoutes();
        foreach ($routes as $route) {
            $actual[] = [$route->getMethod(), $route->getPattern(), $route->getControllerClass()];
        }

        $this->assertSame($expected, $actual);
    }

    public function testNestedMountingAddsCorrectPrefixes() {
        $router = $this->getRouter();
        $mock = $this->createMock(Controller::class);
        $router->mount('/foo', function(FastRouteRouter $router) use($mock) {
            $router->delete('/foo-get', $mock);
            $router->mount('/bar', function(FastRouteRouter $router) use($mock) {
                $router->post('/bar-post', $mock);
                $router->mount('/baz', function(FastRouteRouter $router) use($mock) {
                    $router->put('/baz-put', $mock);
                });
            });
        });

        $expected = [
            ['DELETE', '/foo/foo-get', get_class($mock)],
            ['POST', '/foo/bar/bar-post', get_class($mock)],
            ['PUT', '/foo/bar/baz/baz-put', get_class($mock)]
        ];
        $actual = [];
        foreach ($router->getRoutes() as $route) {
            $actual[] = [$route->getMethod(), $route->getPattern(), $route->getControllerClass()];
        }

        $this->assertSame($expected, $actual);
    }

    public function testSettingNotFoundController() {
        $router = $this->getRouter();
        $mock = $this->createMock(Controller::class);
        $router->setNotFoundController($mock);
        $controller = $router->getNotFoundController();
        $this->assertSame($mock, $controller);
    }

    public function testSettingMethodNotAllowedController() {
        $router = $this->getRouter();
        $mock = $this->createMock(Controller::class);
        $router->setMethodNotAllowedController($mock);
        $controller = $router->getMethodNotAllowedController();
        $this->assertSame($mock, $controller);
    }

    public function testSettingMountedRoot() {
        $router = $this->getRouter();
        $mock = $this->createMock(Controller::class);
        $router->mount('/foo', function($router) use($mock) {
            $router->get($router->root(), $mock);
        });

        $request = $this->getRequest('GET', 'http://example.com/foo');
        $controller = $router->match($request);
        $this->assertSame($mock, $controller);
    }

    public function testUsingRouterRootWithoutMount() {
        $request = $this->getRequest('GET', 'http://example.com');
        $router = $this->getRouter();
        $mock = $this->createMock(Controller::class);
        $router->get($router->root(), $mock);
        $controller = $router->match($request);
        $this->assertSame($mock, $controller);
    }

    public function testUrlDecodingCustomAttributes() {
        $request = $this->getRequest('GET', 'http://example.com/foo%20bar');
        $router = $this->getRouter();
        $mock = $this->createMock(Controller::class);
        $router->get('/{param}', $mock);
        $router->match($request);

        $this->assertSame('foo bar', $request->getAttribute('param'));
    }

}
