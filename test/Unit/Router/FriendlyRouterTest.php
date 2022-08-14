<?php declare(strict_types=1);

namespace Cspray\Labrador\Http\Test\Unit\Router;

use Amp\Http\Server\Driver\Client;
use Amp\Http\Server\Middleware;
use Amp\Http\Server\Request;
use Cspray\Labrador\Http\Controller\Controller;
use Cspray\Labrador\Http\Router\FriendlyRouter;
use Cspray\Labrador\Http\Router\Router;
use League\Uri\Http;
use PHPUnit\Framework\TestCase;

class FriendlyRouterTest extends TestCase {

    private $mockRouter;

    public function setUp() : void {
        parent::setUp();
        $this->mockRouter = $this->createMock(Router::class);
    }

    private function getRouter() : FriendlyRouter {
        return new FriendlyRouter($this->mockRouter);
    }

    public function testMountingRouterAddsPrefix() {
        $mountedController = $this->createMock(Controller::class);
        $unmountedController = $this->createMock(Controller::class);
        $this->mockRouter->expects($this->exactly(2))
                         ->method('addRoute')
                         ->withConsecutive(
                             ['GET', '/prefix/foo', $mountedController],
                             ['GET', '/noprefix', $unmountedController]
                         );

        $router = $this->getRouter();
        $router->mount('/prefix', function (FriendlyRouter $router) use ($mountedController) {
            $router->get('/foo', $mountedController);
        });
        $router->get('/noprefix', $unmountedController);
    }

    public function testNestedMountingAddsCorrectPrefixes() {
        $fooGetController = $this->createMock(Controller::class);
        $barPostController = $this->createMock(Controller::class);
        $bazPutController = $this->createMock(Controller::class);

        $this->mockRouter->expects($this->exactly(3))
                         ->method('addRoute')
                         ->withConsecutive(
                             ['GET', '/foo/foo-get', $fooGetController],
                             ['POST', '/foo/bar/bar-post', $barPostController],
                             ['PUT', '/foo/bar/baz/baz-put', $bazPutController]
                         );

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
    }

    public function testAddingMiddlewareToMountedRoute() {
        $controller = $this->createMock(Controller::class);
        $middlewareA = $this->createMock(Middleware::class);
        $middlewareB = $this->createMock(Middleware::class);

        $this->mockRouter->expects($this->exactly(4))
                         ->method('addRoute')
                         ->withConsecutive(
                             ['GET', '/foo/bar', $controller, $middlewareA, $middlewareB],
                             ['POST', '/foo/bar', $controller, $middlewareA, $middlewareB],
                             ['PUT', '/foo/bar', $controller, $middlewareA, $middlewareB],
                             ['DELETE', '/foo/bar', $controller, $middlewareA, $middlewareB]
                         );

        $router = $this->getRouter();
        $router->mount('/foo', function(FriendlyRouter $router) use($controller) {
            $router->get('/bar', $controller);
            $router->post('/bar', $controller);
            $router->put('/bar', $controller);
            $router->delete('/bar', $controller);
        }, $middlewareA, $middlewareB);
    }

    public function testMultipleMountsOnlyAddsMiddlewareAppropriately() {
        $controller = $this->createMock(Controller::class);
        $middlewareA = $this->createMock(Middleware::class);
        $middlewareB = $this->createMock(Middleware::class);

        $this->mockRouter->expects($this->exactly(2))
                         ->method('addRoute')
                         ->withConsecutive(
                             ['GET', '/foo/bar', $controller, $middlewareA, $middlewareB],
                             ['POST', '/foo/bar', $controller]
                         );

        $router = $this->getRouter();
        $router->mount('/foo', function(FriendlyRouter $router) use($controller) {
            $router->get('/bar', $controller);
        }, $middlewareA, $middlewareB);
        $router->mount('/foo', function(FriendlyRouter $router) use($controller) {
            $router->post('/bar', $controller);
        });
    }

    public function testMultipleNestedMounts() {
        $nestedMountMiddleware = $this->createMock(Middleware::class);
        $controller = $this->createMock(Controller::class);
        $defaultMiddlewares = [
            $this->createMock(Middleware::class),
            $this->createMock(Middleware::class),
        ];
        $expectedMiddlewares = array_merge([], $defaultMiddlewares, [$nestedMountMiddleware]);
        $this->mockRouter->expects($this->once())
                         ->method('addRoute')
                         ->with('GET', '/foo/bar/baz', $controller, ...$expectedMiddlewares);

        $router = $this->getRouter();
        $router->mount('/foo', function(FriendlyRouter $router) use($nestedMountMiddleware, $controller) {
            $router->mount('/bar', function(FriendlyRouter $router) use($nestedMountMiddleware, $controller) {
                $router->get('/baz', $controller);
            }, $nestedMountMiddleware);
        }, ...$defaultMiddlewares);
    }

    public function testMiddlewareAddedToMountedRoute() {
        $routeMiddleware = $this->createMock(Middleware::class);
        $controller = $this->createMock(Controller::class);
        $defaultMiddlewares = [
            $this->createMock(Middleware::class),
            $this->createMock(Middleware::class),
        ];
        $expectedMiddlewares = array_merge([], $defaultMiddlewares, [$routeMiddleware]);
        $this->mockRouter->expects($this->once())
            ->method('addRoute')
            ->with('GET', '/foo/bar', $controller, ...$expectedMiddlewares);

        $router = $this->getRouter();
        $router->mount('/foo', function(FriendlyRouter $router) use($routeMiddleware, $controller) {
            $router->get('/bar', $controller, $routeMiddleware);
        }, ...$defaultMiddlewares);
    }

    public function testDelegateMatchToPassedRouter() {
        $request = new Request($this->createMock(Client::class), 'GET', Http::createFromString('/'));
        $controller = $this->createMock(Controller::class);
        $this->mockRouter->expects($this->once())
                         ->method('match')
                         ->with($request)
                         ->willReturn($controller);

        $router = $this->getRouter();
        $actual = $router->match($request);

        $this->assertSame($controller, $actual);
    }

    public function testDelegateGetRoutesToPassedRouter() {
        $this->mockRouter->expects($this->once())
                         ->method('getRoutes')
                         ->willReturn([]);

        $router = $this->getRouter();
        $this->assertEmpty($router->getRoutes());
    }

    public function testDelegateSetNotFoundController() {
        $controller = $this->createMock(Controller::class);
        $this->mockRouter->expects($this->once())
                         ->method('setNotFoundController')
                         ->with($controller);
        $router = $this->getRouter();
        $router->setNotFoundController($controller);
    }

    public function testDelegateGetNotFoundController() {
        $controller = $this->createMock(Controller::class);
        $this->mockRouter->expects($this->once())
            ->method('getNotFoundController')
            ->willReturn($controller);
        $router = $this->getRouter();
        $actual = $router->getNotFoundController();

        $this->assertSame($controller, $actual);
    }
    
    public function testDelegateSetMethodNotAllowedController() {
        $controller = $this->createMock(Controller::class);
        $this->mockRouter->expects($this->once())
            ->method('setMethodNotAllowedController')
            ->with($controller);
        $router = $this->getRouter();
        $router->setMethodNotAllowedController($controller);
    }

    public function testDelegateGetMethodNotAllowedController() {
        $controller = $this->createMock(Controller::class);
        $this->mockRouter->expects($this->once())
            ->method('getMethodNotAllowedController')
            ->willReturn($controller);
        $router = $this->getRouter();
        $actual = $router->getMethodNotAllowedController();

        $this->assertSame($controller, $actual);
    }
}
