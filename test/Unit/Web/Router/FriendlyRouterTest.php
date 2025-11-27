<?php declare(strict_types=1);

namespace Labrador\Test\Unit\Web\Router;

use Amp\Http\Server\Driver\Client;
use Amp\Http\Server\Middleware;
use Amp\Http\Server\Request;
use Amp\Http\Server\RequestHandler;
use FastRoute\DataGenerator\GroupCountBased as GcbDataGenerator;
use FastRoute\Dispatcher\GroupCountBased as GcbDispatcher;
use FastRoute\RouteCollector;
use FastRoute\RouteParser\Std as StdRouteParser;
use Labrador\Web\HttpMethod;
use Labrador\Web\Router\FastRouteRouter;
use Labrador\Web\Router\FriendlyRouter;
use Labrador\Web\Router\Router;
use Labrador\Web\Router\RoutingResolution;
use Labrador\Web\Router\RoutingResolutionReason;
use League\Uri\Http;
use PHPUnit\Framework\TestCase;

class FriendlyRouterTest extends TestCase {

    public function setUp() : void {
        parent::setUp();
    }

    private function getRouter(Router $router = null) : FriendlyRouter {
        $router = $router ?? new FastRouteRouter(
            new RouteCollector(new StdRouteParser(), new GcbDataGenerator()),
            function($data) { return new GcbDispatcher($data);
            }
        );
        return new FriendlyRouter($router);
    }

    public function testMountingRouterAddsPrefix() {
        $mountedRequestHandler = $this->createMock(RequestHandler::class);
        $unmountedRequestHandler = $this->createMock(RequestHandler::class);

        $router = $this->getRouter();
        $router->mount('/prefix', function (FriendlyRouter $router) use ($mountedRequestHandler) {
            $router->get('/foo', $mountedRequestHandler);
        });
        $router->get('/noprefix', $unmountedRequestHandler);

        $routes = $router->getRoutes();

        self::assertCount(2, $routes);

        self::assertSame(HttpMethod::Get, $routes[0]->requestMapping->getHttpMethod());
        self::assertSame('/prefix/foo', $routes[0]->requestMapping->getPath());
        self::assertSame($mountedRequestHandler, $routes[0]->requestHandler);

        self::assertSame(HttpMethod::Get, $routes[1]->requestMapping->getHttpMethod());
        self::assertSame('/noprefix', $routes[1]->requestMapping->getPath());
        self::assertSame($unmountedRequestHandler, $routes[1]->requestHandler);
    }

    public function testNestedMountingAddsCorrectPrefixes() {
        $fooGet = $this->createMock(RequestHandler::class);
        $barPost = $this->createMock(RequestHandler::class);
        $bazPut = $this->createMock(RequestHandler::class);

        $router = $this->getRouter();
        $router->mount('/foo', function(FriendlyRouter $router) use(
            $fooGet, $barPost, $bazPut
        ) {
            $router->get('/foo-get', $fooGet);
            $router->mount('/bar', function(FriendlyRouter $router) use($barPost, $bazPut) {
                $router->post('/bar-post', $barPost);
                $router->mount('/baz', function(FriendlyRouter $router) use($bazPut) {
                    $router->put('/baz-put', $bazPut);
                });
            });
        });

        $routes = $router->getRoutes();
        self::assertCount(3, $routes);

        self::assertSame(HttpMethod::Get, $routes[0]->requestMapping->getHttpMethod());
        self::assertSame('/foo/foo-get', $routes[0]->requestMapping->getPath());
        self::assertSame($fooGet, $routes[0]->requestHandler);

        self::assertSame(HttpMethod::Post, $routes[1]->requestMapping->getHttpMethod());
        self::assertSame('/foo/bar/bar-post', $routes[1]->requestMapping->getPath());
        self::assertSame($barPost, $routes[1]->requestHandler);

        self::assertSame(HttpMethod::Put, $routes[2]->requestMapping->getHttpMethod());
        self::assertSame('/foo/bar/baz/baz-put', $routes[2]->requestMapping->getPath());
        self::assertSame($bazPut, $routes[2]->requestHandler);
    }

    public function testAddingMiddlewareToMountedRoute() {
        $requestHandler = $this->createMock(RequestHandler::class);
        $middlewareA = $this->createMock(Middleware::class);
        $middlewareB = $this->createMock(Middleware::class);

        $router = $this->getRouter();
        $router->mount('/foo', function(FriendlyRouter $router) use($requestHandler) {
            $router->get('/bar', $requestHandler);
            $router->post('/bar', $requestHandler);
            $router->put('/bar', $requestHandler);
            $router->delete('/bar', $requestHandler);
        }, $middlewareA, $middlewareB);

        $routes = $router->getRoutes();

        self::assertCount(4, $routes);

        self::assertSame($requestHandler, $routes[0]->requestHandler);
        self::assertSame([$middlewareA, $middlewareB], $routes[0]->middleware);

        self::assertSame($requestHandler, $routes[1]->requestHandler);
        self::assertSame([$middlewareA, $middlewareB], $routes[1]->middleware);

        self::assertSame($requestHandler, $routes[1]->requestHandler);
        self::assertSame([$middlewareA, $middlewareB], $routes[1]->middleware);

        self::assertSame($requestHandler, $routes[2]->requestHandler);
        self::assertSame([$middlewareA, $middlewareB], $routes[2]->middleware);

        self::assertSame($requestHandler, $routes[3]->requestHandler);
        self::assertSame([$middlewareA, $middlewareB], $routes[3]->middleware);
    }

    public function testMultipleMountsOnlyAddsMiddlewareAppropriately() {
        $requestHandler = $this->createMock(RequestHandler::class);
        $middlewareA = $this->createMock(Middleware::class);
        $middlewareB = $this->createMock(Middleware::class);

        $router = $this->getRouter();
        $router->mount('/foo', function(FriendlyRouter $router) use($requestHandler) {
            $router->get('/bar', $requestHandler);
        }, $middlewareA, $middlewareB);
        $router->mount('/foo', function(FriendlyRouter $router) use($requestHandler) {
            $router->post('/bar', $requestHandler);
        });

        $routes = $router->getRoutes();
        self::assertCount(2, $routes);

        self::assertSame($requestHandler, $routes[0]->requestHandler);
        self::assertSame([$middlewareA, $middlewareB], $routes[0]->middleware);
    }

    public function testMultipleNestedMounts() {
        $nestedMountMiddleware = $this->createMock(Middleware::class);
        $requestHandler = $this->createMock(RequestHandler::class);
        $defaultMiddlewares = [
            $this->createMock(Middleware::class),
            $this->createMock(Middleware::class),
        ];
        $expectedMiddlewares = array_merge([], $defaultMiddlewares, [$nestedMountMiddleware]);

        $router = $this->getRouter();
        $router->mount('/foo', function(FriendlyRouter $router) use($nestedMountMiddleware, $requestHandler) {
            $router->mount('/bar', function(FriendlyRouter $router) use($nestedMountMiddleware, $requestHandler) {
                $router->get('/baz', $requestHandler);
            }, $nestedMountMiddleware);
        }, ...$defaultMiddlewares);

        $routes = $router->getRoutes();

        self::assertCount(1, $routes);
        self::assertSame($requestHandler, $routes[0]->requestHandler);
        self::assertSame($expectedMiddlewares, $routes[0]->middleware);
    }

    public function testDelegateMatch() : void {
        $request = new Request(
            $this->getMockBuilder(Client::class)->getMock(),
            'GET',
            Http::createFromString('http://example.com')
        );
        $router = $this->getMockBuilder(Router::class)->getMock();
        $router->expects($this->once())
            ->method('match')
            ->with($request)
            ->willReturn($resolution = new RoutingResolution(null, [], RoutingResolutionReason::NotFound));

        $this->getRouter($router)->match($request);
    }
}
