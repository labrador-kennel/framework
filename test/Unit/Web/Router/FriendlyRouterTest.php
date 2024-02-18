<?php declare(strict_types=1);

namespace Labrador\Test\Unit\Web\Router;

use Amp\Http\Server\Driver\Client;
use Amp\Http\Server\Middleware;
use Amp\Http\Server\Request;
use FastRoute\DataGenerator\GroupCountBased as GcbDataGenerator;
use FastRoute\Dispatcher\GroupCountBased as GcbDispatcher;
use FastRoute\RouteCollector;
use FastRoute\RouteParser\Std as StdRouteParser;
use Labrador\Web\Controller\Controller;
use Labrador\Web\Controller\MiddlewareController;
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
            function($data) { return new GcbDispatcher($data); }
        );
        return new FriendlyRouter($router);
    }

    public function testMountingRouterAddsPrefix() {
        $mountedController = $this->createMock(Controller::class);
        $unmountedController = $this->createMock(Controller::class);

        $router = $this->getRouter();
        $router->mount('/prefix', function (FriendlyRouter $router) use ($mountedController) {
            $router->get('/foo', $mountedController);
        });
        $router->get('/noprefix', $unmountedController);

        $routes = $router->getRoutes();

        self::assertCount(2, $routes);

        self::assertSame(HttpMethod::Get, $routes[0]->requestMapping->getHttpMethod());
        self::assertSame('/prefix/foo', $routes[0]->requestMapping->getPath());
        self::assertSame($mountedController, $routes[0]->controller);

        self::assertSame(HttpMethod::Get, $routes[1]->requestMapping->getHttpMethod());
        self::assertSame('/noprefix', $routes[1]->requestMapping->getPath());
        self::assertSame($unmountedController, $routes[1]->controller);
    }

    public function testNestedMountingAddsCorrectPrefixes() {
        $fooGetController = $this->createMock(Controller::class);
        $barPostController = $this->createMock(Controller::class);
        $bazPutController = $this->createMock(Controller::class);

        $router = $this->getRouter();
        $router->mount('/foo', function(FriendlyRouter $router) use(
            $fooGetController, $barPostController, $bazPutController
        ) {
            $router->get('/foo-get', $fooGetController);
            $router->mount('/bar', function(FriendlyRouter $router) use($barPostController, $bazPutController) {
                $router->post('/bar-post', $barPostController);
                $router->mount('/baz', function(FriendlyRouter $router) use($bazPutController) {
                    $router->put('/baz-put', $bazPutController);
                });
            });
        });

        $routes = $router->getRoutes();
        self::assertCount(3, $routes);

        self::assertSame(HttpMethod::Get, $routes[0]->requestMapping->getHttpMethod());
        self::assertSame('/foo/foo-get', $routes[0]->requestMapping->getPath());
        self::assertSame($fooGetController, $routes[0]->controller);

        self::assertSame(HttpMethod::Post, $routes[1]->requestMapping->getHttpMethod());
        self::assertSame('/foo/bar/bar-post', $routes[1]->requestMapping->getPath());
        self::assertSame($barPostController, $routes[1]->controller);

        self::assertSame(HttpMethod::Put, $routes[2]->requestMapping->getHttpMethod());
        self::assertSame('/foo/bar/baz/baz-put', $routes[2]->requestMapping->getPath());
        self::assertSame($bazPutController, $routes[2]->controller);
    }

    public function testAddingMiddlewareToMountedRoute() {
        $controller = $this->createMock(Controller::class);
        $middlewareA = $this->createMock(Middleware::class);
        $middlewareB = $this->createMock(Middleware::class);

        $router = $this->getRouter();
        $router->mount('/foo', function(FriendlyRouter $router) use($controller) {
            $router->get('/bar', $controller);
            $router->post('/bar', $controller);
            $router->put('/bar', $controller);
            $router->delete('/bar', $controller);
        }, $middlewareA, $middlewareB);

        $routes = $router->getRoutes();

        self::assertCount(4, $routes);

        self::assertInstanceOf(MiddlewareController::class, $routes[0]->controller);
        self::assertSame([$middlewareA, $middlewareB], $routes[0]->controller->middlewares);

        self::assertInstanceOf(MiddlewareController::class, $routes[1]->controller);
        self::assertSame([$middlewareA, $middlewareB], $routes[1]->controller->middlewares);

        self::assertInstanceOf(MiddlewareController::class, $routes[1]->controller);
        self::assertSame([$middlewareA, $middlewareB], $routes[1]->controller->middlewares);

        self::assertInstanceOf(MiddlewareController::class, $routes[2]->controller);
        self::assertSame([$middlewareA, $middlewareB], $routes[2]->controller->middlewares);

        self::assertInstanceOf(MiddlewareController::class, $routes[3]->controller);
        self::assertSame([$middlewareA, $middlewareB], $routes[3]->controller->middlewares);
    }

    public function testMultipleMountsOnlyAddsMiddlewareAppropriately() {
        $controller = $this->createMock(Controller::class);
        $middlewareA = $this->createMock(Middleware::class);
        $middlewareB = $this->createMock(Middleware::class);

        $router = $this->getRouter();
        $router->mount('/foo', function(FriendlyRouter $router) use($controller) {
            $router->get('/bar', $controller);
        }, $middlewareA, $middlewareB);
        $router->mount('/foo', function(FriendlyRouter $router) use($controller) {
            $router->post('/bar', $controller);
        });

        $routes = $router->getRoutes();
        self::assertCount(2, $routes);

        self::assertInstanceOf(MiddlewareController::class, $routes[0]->controller);
        self::assertSame([$middlewareA, $middlewareB], $routes[0]->controller->middlewares);
        self::assertSame($controller, $routes[1]->controller);
    }

    public function testMultipleNestedMounts() {
        $nestedMountMiddleware = $this->createMock(Middleware::class);
        $controller = $this->createMock(Controller::class);
        $defaultMiddlewares = [
            $this->createMock(Middleware::class),
            $this->createMock(Middleware::class),
        ];
        $expectedMiddlewares = array_merge([], $defaultMiddlewares, [$nestedMountMiddleware]);

        $router = $this->getRouter();
        $router->mount('/foo', function(FriendlyRouter $router) use($nestedMountMiddleware, $controller) {
            $router->mount('/bar', function(FriendlyRouter $router) use($nestedMountMiddleware, $controller) {
                $router->get('/baz', $controller);
            }, $nestedMountMiddleware);
        }, ...$defaultMiddlewares);

        $routes = $router->getRoutes();

        self::assertCount(1, $routes);
        self::assertInstanceOf(MiddlewareController::class, $routes[0]->controller);
        self::assertSame($expectedMiddlewares, $routes[0]->controller->middlewares);
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
            ->willReturn($resolution = new RoutingResolution(null, RoutingResolutionReason::NotFound));

        $this->getRouter($router)->match($request);
    }

}
