<?php

/**
 *
 * @license See LICENSE in source root
 * @version 1.0
 * @since   1.0
 */

namespace Cspray\Labrador\Http\Test\Router;

use function Amp\call;
use Amp\Http\Server\Driver\Client;
use Amp\Http\Server\Middleware;
use Amp\Http\Server\RequestHandler;
use Amp\Promise;
use Amp\Success;
use Cspray\Labrador\Http\Controller\Controller;
use Cspray\Labrador\Http\Router\FastRouteRouter;
use Cspray\Labrador\Http\Router\Route;
use Cspray\Labrador\Http\Exception\InvalidTypeException;
use Cspray\Labrador\Http\Test\AsyncTestCase;
use Cspray\Labrador\Http\Test\Stub\RequestDecoratorMiddleware;
use Cspray\Labrador\Http\Test\Stub\ResponseControllerStub;
use Cspray\Labrador\Http\Test\Stub\ResponseDecoratorMiddleware;
use Cspray\Labrador\Http\Test\Stub\ToStringControllerStub;
use FastRoute\DataGenerator\GroupCountBased as GcbDataGenerator;
use FastRoute\Dispatcher\GroupCountBased as GcbDispatcher;
use FastRoute\RouteCollector;
use FastRoute\RouteParser\Std as StdRouteParser;
use Amp\Http\Server\Request;
use Amp\Http\Server\Response;
use Amp\Http\Status;
use League\Uri\Http;

class FastRouteRouterTest extends AsyncTestCase {

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
            function($data) { return new GcbDispatcher($data);
            }
        );
    }

    private function getRequest(string $method, string $uri) : Request {
        return new Request($this->client, $method, Http::createFromString($uri));
    }

    /**
     * @throws InvalidTypeException
     */
    public function testFastRouteDispatcherCallbackReturnsImproperTypeThrowsException() {
        $router = new FastRouteRouter(
            new RouteCollector(new StdRouteParser(), new GcbDataGenerator()),
            function() { return 'not a dispatcher';
            }
        );

        $this->expectException(InvalidTypeException::class);
        $expectedMsg = 'A FastRoute\\Dispatcher must be returned from dispatcher callback injected in constructor';
        $this->expectExceptionMessage($expectedMsg);

        $router->match($this->getRequest('GET', '/'));
    }

    /**
     * @throws InvalidTypeException
     */
    public function testRouterNotFoundReturnsDefaultController() {
        $router = $this->getRouter();
        $request = $this->getRequest('GET', '/');
        $controller = $router->match($request);
        /** @var Response $response */
        $response = yield $controller->handleRequest($request);
        $body = yield $response->getBody()->read();
        $this->assertSame(Status::NOT_FOUND, $response->getStatus());
        $this->assertSame('Not Found', $body);
        $this->assertSame('DefaultNotFoundController', $controller->toString());
    }

    public function testRouterMethodNotAllowedReturnsCorrectController() {
        $router = $this->getRouter();
        $request = $this->getRequest('POST', 'http://labrador.dev/foo');
        $mock = $this->createMock(Controller::class);
        $router->addRoute('GET', '/foo', $mock);
        $router->addRoute('PUT', '/foo', $mock);

        $controller = $router->match($request);
        /** @var Response $response */
        $response = yield $controller->handleRequest($request);
        $body = yield $response->getBody()->read();
        $this->assertSame(Status::METHOD_NOT_ALLOWED, $response->getStatus());
        $this->assertSame('Method Not Allowed', $body);
        $this->assertSame('DefaultMethodNotAllowedController', $controller->toString());
    }

    public function testRouterIsOkReturnsCorrectController() {
        $router = $this->getRouter();

        $request = $this->getRequest('GET', 'http://labrador.dev/foo');
        $router->addRoute('GET', '/foo', new ResponseControllerStub(new Response(200, [], 'test val')));

        $controller = $router->match($request);
        /** @var Response $response */
        $response = yield $controller->handleRequest($request);
        $body = yield $response->getBody()->read();

        $this->assertSame(Status::OK, $response->getStatus());
        $this->assertSame('test val', $body);
    }

    public function testRouteWithParametersSetOnRequestAttributes() {
        $router = $this->getRouter();

        $request = $this->getRequest('POST', 'http://www.sprog.dev/foo/bar/qux');
        $router->addRoute('POST', '/foo/{name}/{id}', $this->createMock(Controller::class));

        $router->match($request);

        $this->assertSame('bar', $request->getAttribute('name'));
        $this->assertSame('qux', $request->getAttribute('id'));
    }

    public function testGetRoutesWithJustOne() {
        $router = $this->getRouter();
        $router->addRoute('GET', '/foo', new ToStringControllerStub('foo_get'));

        $routes = $router->getRoutes();
        $this->assertCount(1, $routes);
        $this->assertInstanceOf(Route::class, $routes[0]);
        $this->assertSame('/foo', $routes[0]->getPattern());
        $this->assertSame('GET', $routes[0]->getMethod());
        $this->assertSame('foo_get', $routes[0]->getControllerDescription());
    }

    public function testGetRoutesWithOnePatternSupportingMultipleMethods() {
        $router = $this->getRouter();
        $router->addRoute('GET', '/foo/bar', new ToStringControllerStub('foo_bar_get'));
        $router->addRoute('POST', '/foo/bar', new ToStringControllerStub('foo_bar_post'));
        $router->addRoute('PUT', '/foo/bar', new ToStringControllerStub('foo_bar_put'));

        $expected = [
            ['GET', '/foo/bar', 'foo_bar_get'],
            ['POST', '/foo/bar', 'foo_bar_post'],
            ['PUT', '/foo/bar', 'foo_bar_put']
        ];
        $actual = [];
        $routes = $router->getRoutes();
        foreach ($routes as $route) {
            $this->assertInstanceOf(Route::class, $route);
            $actual[] = [$route->getMethod(), $route->getPattern(), $route->getControllerDescription()];
        }

        $this->assertSame($expected, $actual);
    }

    public function testGetRoutesWithStaticAndVariable() {
        $router = $this->getRouter();
        $router->addRoute('GET', '/foo/bar/{id}', new ToStringControllerStub('foo_bar_show_get'));
        $router->addRoute('GET', '/foo/baz/{name}', new ToStringControllerStub('foo_bar_show_name_get'));
        $router->addRoute('POST', '/foo/baz', new ToStringControllerStub('foo_baz_post'));
        $router->addRoute('PUT', '/foo/quz', new ToStringControllerStub('foo_quz_put'));

        $expected = [
            ['GET', '/foo/bar/{id}', 'foo_bar_show_get'],
            ['GET', '/foo/baz/{name}', 'foo_bar_show_name_get'],
            ['POST', '/foo/baz', 'foo_baz_post'],
            ['PUT', '/foo/quz', 'foo_quz_put']
        ];
        $actual = [];
        $routes = $router->getRoutes();
        foreach ($routes as $route) {
            $this->assertInstanceOf(Route::class, $route);
            $actual[] = [$route->getMethod(), $route->getPattern(), $route->getControllerDescription()];
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

    public function testUrlDecodingCustomAttributes() {
        $request = $this->getRequest('GET', 'http://example.com/foo%20bar');
        $router = $this->getRouter();
        $mock = $this->createMock(Controller::class);
        $router->addRoute('GET', '/{param}', $mock);
        $router->match($request);

        $this->assertSame('foo bar', $request->getAttribute('param'));
    }

    private function defaultMiddlewares() {
        $requestDecorator = new RequestDecoratorMiddleware();
        $responseDecorator = new ResponseDecoratorMiddleware();

        return [$requestDecorator, $responseDecorator];
    }

    public function routerRouteMethodProvider() {
        return [
            ['GET'],
            ['POST'],
            ['PUT'],
            ['DELETE'],
        ];
    }

    /**
     * @param string $httpMethod
     * @throws InvalidTypeException
     * @dataProvider routerRouteMethodProvider
     */
    public function testAddingMiddlewareToSingleRoute(string $httpMethod) {
        $request = $this->getRequest($httpMethod, 'http://example.com/foo');
        $router = $this->getRouter();
        $responseController = new ResponseControllerStub(new Response(200, [], 'decorated value:'));
        $router->addRoute($httpMethod, '/foo', $responseController, ...$this->defaultMiddlewares());

        $controller = $router->match($request);

        /** @var Response $response */
        $response = yield $controller->handleRequest($request);
        $body = yield $response->getBody()->read();

        $this->assertSame('decorated value: foobar', $body);
    }
}
