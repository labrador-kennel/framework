<?php

/**
 *
 * @license See LICENSE in source root
 * @version 1.0
 * @since   1.0
 */

namespace Cspray\Labrador\Http\Test\Router;

use Cspray\Labrador\Http\HandlerResolver\HandlerResolver;
use Cspray\Labrador\Http\Router\ResolvedRoute;
use Cspray\Labrador\Http\Router\FastRouteRouter;
use Cspray\Labrador\Http\Router\Route;
use Cspray\Labrador\Http\Exception\InvalidHandlerException;
use Cspray\Labrador\Http\Exception\InvalidTypeException;
use Cspray\Labrador\Http\StatusCodes;
use FastRoute\DataGenerator\GroupCountBased as GcbDataGenerator;
use FastRoute\Dispatcher\GroupCountBased as GcbDispatcher;
use FastRoute\RouteCollector;
use FastRoute\RouteParser\Std as StdRouteParser;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Zend\Diactoros\ServerRequest as Request;
use PHPUnit_Framework_TestCase as UnitTestCase;
use Zend\Diactoros\Uri;

class FastRouteRouterTest extends UnitTestCase {

    private $mockResolver;

    private function getRouter() {
        $this->mockResolver = $this->getMock(HandlerResolver::class);
        return new FastRouteRouter(
            $this->mockResolver,
            new RouteCollector(new StdRouteParser(), new GcbDataGenerator()),
            function($data) { return new GcbDispatcher($data); }
        );
    }

    public function testFastRouteDispatcherCallbackReturnsImproperTypeThrowsException() {
        $mockResolver = $this->getMock(HandlerResolver::class);
        $router = new FastRouteRouter(
            $mockResolver,
            new RouteCollector(new StdRouteParser(), new GcbDataGenerator()),
            function() { return 'not a dispatcher'; }
        );

        $this->expectException(InvalidTypeException::class);
        $this->expectExceptionMessage('A FastRoute\\Dispatcher must be returned from dispatcher callback injected in constructor');

        $router->match(new Request());
    }

    public function testRouterNotFoundReturnsCorrectResolvedRoute() {
        $router = $this->getRouter();
        $resolved = $router->match(new Request());
        $this->assertInstanceOf(ResolvedRoute::class, $resolved);
        $this->assertTrue($resolved->isNotFound());
        $handler = $resolved->getController();
        /** @var ResponseInterface $response */
        $response = $handler(new Request());
        $this->assertSame(StatusCodes::NOT_FOUND, $response->getStatusCode());
        $this->assertSame('Not Found', $response->getBody()->getContents());
    }

    public function testRouterMethodNotAllowedReturnsCorrectResolvedRoute() {
        $router = $this->getRouter();
        $request = (new Request())->withMethod('POST')
            ->withUri(new Uri('http://labrador.dev/foo'));
        $router->get('/foo', 'foo#bar');
        $router->put('/foo', 'foo#baz');

        $resolved = $router->match($request);
        $this->assertInstanceOf(ResolvedRoute::class, $resolved);
        $this->assertTrue($resolved->isMethodNotAllowed());
        $this->assertSame(['GET', 'PUT'], $resolved->getAvailableMethods());
        $handler = $resolved->getController();
        /** @var ResponseInterface $response */
        $response = $handler($request);
        $this->assertSame(StatusCodes::METHOD_NOT_ALLOWED, $response->getStatusCode());
        $this->assertSame('Method Not Allowed', $response->getBody()->getContents());
    }

    public function testRouterIsOkReturnsCorrectResolvedRoute() {
        $router = $this->getRouter();

        $request = (new Request())->withMethod('GET')
            ->withUri(new Uri('http://labrador.dev/foo'));
        $router->get('/foo', 'handler');
        $this->mockResolver->expects($this->once())
                           ->method('resolve')
                           ->with($this->isInstanceOf(ServerRequestInterface::class), 'handler')
                           ->willReturn(function() { return 'OK'; });

        $resolved = $router->match($request);
        $this->assertInstanceOf(ResolvedRoute::class, $resolved);
        $this->assertTrue($resolved->isOk());
        $handler = $resolved->getController();
        $this->assertSame('OK', $handler());
    }

    public function testRouteWithParametersSetOnRequestAttributes() {
        $router = $this->getRouter();

        $router->post('/foo/{name}/{id}', 'attr#action');

        /** @var ServerRequestInterface $request */

        $request = (new Request())->withMethod('POST')
                                  ->withUri(new Uri('http://www.sprog.dev/foo/bar/qux'));
        $this->mockResolver->expects($this->once())
                           ->method('resolve')
                           ->with($this->isInstanceOf(ServerRequestInterface::class), 'attr#action')
                           ->willReturn(function() { return 'OK'; });

        $resolved = $router->match($request);

        $this->assertSame('bar', $resolved->getRequest()->getAttribute('name'));
        $this->assertSame('qux', $resolved->getRequest()->getAttribute('id'));
    }

    public function testLabradorMetaRequestDataSetOnRequestAttributes() {
        $router = $this->getRouter();

        $router->post('/foo', 'controller#action');

        $request = (new Request())->withMethod('POST')
                                  ->withUri(new Uri('http://labrador.dev/foo'));
        $this->mockResolver->expects($this->once())
                           ->method('resolve')
                           ->with($this->isInstanceOf(ServerRequestInterface::class), 'controller#action')
                           ->will($this->returnValue(function() { return 'OK'; }));

        $resolved = $router->match($request);

        $this->assertSame(['handler' => 'controller#action'], $resolved->getRequest()->getAttribute('_labrador'));
    }

    public function testGetRoutesWithJustOne() {
        $router = $this->getRouter();
        $router->get('/foo', 'handler');

        $routes = $router->getRoutes();
        $this->assertCount(1, $routes);
        $this->assertInstanceOf(Route::class, $routes[0]);
        $this->assertSame('/foo', $routes[0]->getPattern());
        $this->assertSame('GET', $routes[0]->getMethod());
        $this->assertSame('handler', $routes[0]->getHandler());
    }

    public function testGetRoutesWithOnePatternSupportingMultipleMethods() {
        $router = $this->getRouter();
        $router->get('/foo/bar', 'foo_bar_get');
        $router->post('/foo/bar', 'foo_bar_post');
        $router->put('/foo/bar', 'foo_bar_put');

        $expected = [
            ['GET', '/foo/bar', 'foo_bar_get'],
            ['POST', '/foo/bar', 'foo_bar_post'],
            ['PUT', '/foo/bar', 'foo_bar_put']
        ];
        $actual = [];
        $routes = $router->getRoutes();
        foreach ($routes as $route) {
            $this->assertInstanceOf(Route::class, $route);
            $actual[] = [$route->getMethod(), $route->getPattern(), $route->getHandler()];
        }

        $this->assertSame($expected, $actual);
    }

    public function testGetRoutesWithStaticAndVariable() {
        $router = $this->getRouter();
        $router->get('/foo/bar/{id}', 'foo_bar_id');
        $router->get('/foo/baz/{name}', 'foo_baz_name');
        $router->post('/foo/baz', 'foo_baz_post');
        $router->put('/foo/quz', 'foo_quz_put');

        $expected = [
            ['GET', '/foo/bar/{id}', 'foo_bar_id'],
            ['GET', '/foo/baz/{name}', 'foo_baz_name'],
            ['POST', '/foo/baz', 'foo_baz_post'],
            ['PUT', '/foo/quz', 'foo_quz_put']
        ];
        $actual = [];
        $routes = $router->getRoutes();
        foreach ($routes as $route) {
            $this->assertInstanceOf(Route::class, $route);
            $actual[] = [$route->getMethod(), $route->getPattern(), $route->getHandler()];
        }

        $this->assertSame($expected, $actual);
    }

    public function testMountingRouterAddsPrefix() {
        $router = $this->getRouter();
        $router->mount('/prefix', function(FastRouteRouter $router) {
            $router->get('/foo', 'something');
        });
        $router->get('/noprefix', 'something else');

        $expected = [
            ['GET', '/prefix/foo', 'something'],
            ['GET', '/noprefix', 'something else']
        ];
        $actual = [];
        $routes = $router->getRoutes();
        foreach ($routes as $route) {
            $actual[] = [$route->getMethod(), $route->getPattern(), $route->getHandler()];
        }

        $this->assertSame($expected, $actual);
    }

    public function testNestedMountingAddsCorrectPrefixes() {
        $router = $this->getRouter();
        $router->mount('/foo', function(FastRouteRouter $router) {
            $router->delete('/foo-get', 'one');
            $router->mount('/bar', function(FastRouteRouter $router) {
                $router->post('/bar-post', 'two');
                $router->mount('/baz', function(FastRouteRouter $router) {
                    $router->put('/baz-put', 'three');
                });
            });
        });

        $expected = [
            ['DELETE', '/foo/foo-get', 'one'],
            ['POST', '/foo/bar/bar-post', 'two'],
            ['PUT', '/foo/bar/baz/baz-put', 'three']
        ];
        $actual = [];
        foreach ($router->getRoutes() as $route) {
            $actual[] = [$route->getMethod(), $route->getPattern(), $route->getHandler()];
        }

        $this->assertSame($expected, $actual);
    }

    public function testResolverReturnsFalseThrowsException() {
        $router = $this->getRouter();
        $this->mockResolver->expects($this->once())->method('resolve')->willReturn(false);
        $router->get('/foo', 'something');

        $this->expectException(InvalidHandlerException::class);
        $this->expectExceptionMessage('Could not resolve matched handler to a callable controller');

        $request = (new Request())->withMethod('GET')
                                  ->withUri(new Uri('http://labrador.dev/foo'));

        $router->match($request);
    }

    public function testSettingNotFoundController() {
        $router = $this->getRouter();
        $router->setNotFoundController(function() { return 'the set controller'; });
        $controller = $router->getNotFoundController();
        $this->assertSame('the set controller', $controller());
    }

    public function testSettingMethodNotAllowedController() {
        $router = $this->getRouter();
        $router->setMethodNotAllowedController(function() { return 'the set controller'; });
        $controller = $router->getMethodNotAllowedController();
        $this->assertSame('the set controller', $controller());
    }

    public function testSettingMountedRoot() {
        $router = $this->getRouter();
        $router->mount('/foo', function($router) {
            $router->get($router->root(), 'something');
        });

        $request = (new Request())->withMethod('GET')
                                  ->withUri(new Uri('http://example.com/foo'));
        $this->mockResolver->expects($this->once())
                           ->method('resolve')
                           ->with($this->isInstanceOf(ServerRequestInterface::class), 'something')
                           ->willReturn(function() { return 'the set controller'; });

        $resolved = $router->match($request);
        $controller = $resolved->getController();
        $this->assertSame('the set controller', $controller());
    }

    public function testUsingRouterRootWithoutMount() {
        $request = (new Request())->withMethod('GET')
            ->withUri(new Uri('http://example.com'));
        $router = $this->getRouter();
        $router->get($router->root(), 'something');
        $this->mockResolver->expects($this->once())
             ->method('resolve')
             ->with($this->isInstanceOf(ServerRequestInterface::class), 'something')
             ->willReturn(function() { return 'the set controller'; });

        $resolved = $router->match($request);
        $controller = $resolved->getController();
        $this->assertSame('the set controller', $controller());
    }

    public function testUrlDecodingCustomAttributes() {
        $request = (new Request())->withMethod('GET')
            ->withUri(new Uri('http://example.com/foo%20bar'));
        $router = $this->getRouter();
        $router->get('/{param}', 'something');
        $this->mockResolver->expects($this->once())
                           ->method('resolve')
                           ->with($this->isInstanceOf(ServerRequestInterface::class), 'something')
                           ->willReturn(function() { return ''; });

        $resolved = $router->match($request);

        $this->assertTrue($resolved->isOk());
        $this->assertSame('foo bar', $resolved->getRequest()->getAttribute('param'));
    }

}
