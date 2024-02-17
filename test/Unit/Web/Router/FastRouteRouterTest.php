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
use Amp\Http\Server\Response;
use Amp\PHPUnit\AsyncTestCase;
use FastRoute\DataGenerator\GroupCountBased as GcbDataGenerator;
use FastRoute\Dispatcher\GroupCountBased as GcbDispatcher;
use FastRoute\RouteCollector;
use FastRoute\RouteParser\Std as StdRouteParser;
use Labrador\Test\Unit\Web\Stub\RequestDecoratorMiddleware;
use Labrador\Test\Unit\Web\Stub\ResponseControllerStub;
use Labrador\Test\Unit\Web\Stub\ResponseDecoratorMiddleware;
use Labrador\Test\Unit\Web\Stub\ToStringControllerStub;
use Labrador\Web\Controller\Controller;
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
        $router->addRoute(new GetMapping('/foo'), $mock);
        $router->addRoute(new PutMapping('/foo'), $mock);

        $resolution = $router->match($request);

        self::assertNull($resolution->controller);
        self::assertSame($resolution->reason, RoutingResolutionReason::MethodNotAllowed);
    }

    public function testRouterIsOkReturnsCorrectController() {
        $router = $this->getRouter();

        $request = $this->getRequest('GET', 'http://labrador.dev/foo');
        $router->addRoute(
            new GetMapping('/foo'),
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
            new PostMapping('/foo/{name}/{id}'),
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
            new GetMapping('/foo'),
            new ToStringControllerStub('foo_get')
        );

        $routes = $router->getRoutes();
        self::assertCount(1, $routes);
        self::assertInstanceOf(Route::class, $routes[0]);
        self::assertSame('/foo', $routes[0]->requestMapping->getPath());
        self::assertSame(HttpMethod::Get, $routes[0]->requestMapping->getHttpMethod());
        self::assertSame('foo_get', $routes[0]->controller->toString());
    }

    public function testGetRoutesWithOnePatternSupportingMultipleMethods() {
        $router = $this->getRouter();
        $router->addRoute(
            new GetMapping('/foo/bar'),
            new ToStringControllerStub('foo_bar_get')
        );
        $router->addRoute(
            new PostMapping('/foo/bar'),
            new ToStringControllerStub('foo_bar_post')
        );
        $router->addRoute(
            new PutMapping('/foo/bar'),
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
            $actual[] = [$route->requestMapping->getHttpMethod(), $route->requestMapping->getPath(), $route->controller->toString()];
        }

        self::assertSame($expected, $actual);
    }

    public function testGetRoutesWithStaticAndVariable() {
        $router = $this->getRouter();
        $router->addRoute(
            new GetMapping('/foo/bar/{id}'),
            new ToStringControllerStub('foo_bar_show_get')
        );
        $router->addRoute(
            new GetMapping('/foo/baz/{name}'),
            new ToStringControllerStub('foo_bar_show_name_get')
        );
        $router->addRoute(
            new PostMapping('/foo/baz'),
            new ToStringControllerStub('foo_baz_post')
        );
        $router->addRoute(
            new PutMapping('/foo/quz'),
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
            $actual[] = [$route->requestMapping->getHttpMethod(), $route->requestMapping->getPath(), $route->controller->toString()];
        }

        $this->assertSame($expected, $actual);
    }


    public function testUrlDecodingCustomAttributes() {
        $request = $this->getRequest('GET', 'http://example.com/foo%20bar');
        $router = $this->getRouter();
        $mock = $this->createMock(Controller::class);
        $router->addRoute(new GetMapping('/{param}'), $mock);
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
            $mappingClass = 'Labrador\\Web\\Router\\Mapping\\' . $method->name . 'Mapping';
            $args[$method->value] = [new $mappingClass('/foo')];
        }
        return $args;
    }

    /**
     * @dataProvider routerRouteMethodProvider
     */
    public function testAddingMiddlewareToSingleRoute(RequestMapping $requestMapping) {
        $request = $this->getRequest($requestMapping->getHttpMethod()->value, 'http://example.com' . $requestMapping->getPath());
        $router = $this->getRouter();
        $responseController = new ResponseControllerStub(new Response(200, [], 'decorated value:'));
        $router->addRoute(
            $requestMapping,
            $responseController,
            ...$this->defaultMiddlewares()
        );

        $controller = $router->match($request)->controller;

        self::assertNotNull($controller);

        $response = $controller->handleRequest($request);
        $body = $response->getBody()->read();

        $this->assertSame('decorated value: foobar', $body);
    }

    public function testTrailingSlashWithMatchedPathDoesNotResultIn404() : void {
        $request = $this->getRequest(HttpMethod::Get->value, 'http://example.com/found-controller/');
        $router = $this->getRouter();
        $responseController = new ResponseControllerStub(new Response(200, [], 'found controller with trailing slash'));
        $router->addRoute(
            new GetMapping('/found-controller'),
            $responseController,
        );

        $controller = $router->match($request)->controller;

        self::assertNotNull($controller);

        $response = $controller->handleRequest($request);


        self::assertSame(HttpStatus::OK, $response->getStatus());
        self::assertSame('found controller with trailing slash', $response->getBody()->read());
    }

    public function testTrailingSlashInAddedRouteDoesNotResultIn404() : void {
        $request = $this->getRequest(HttpMethod::Get->value, 'http://example.com/found-controller');
        $router = $this->getRouter();
        $responseController = new ResponseControllerStub(new Response(200, [], 'found controller with trailing slash'));
        $router->addRoute(
            new GetMapping('/found-controller/'),
            $responseController,
        );

        $controller = $router->match($request)->controller;

        self::assertNotNull($controller);

        $response = $controller->handleRequest($request);


        self::assertSame(HttpStatus::OK, $response->getStatus());
        self::assertSame('found controller with trailing slash', $response->getBody()->read());
    }
}
