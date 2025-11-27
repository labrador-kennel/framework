<?php

/**
 *
 * @license See LICENSE in source root
 * @version 1.0
 * @since   1.0
 */

namespace Labrador\Test\Unit\Web\Router;

use Amp\Http\Server\Response;
use Labrador\Test\Unit\Web\Stub\ResponseRequestHandlerStub;
use Labrador\Web\Router\Mapping\GetMapping;
use Labrador\Web\Router\Route;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class RouteTest extends TestCase {

    public static function routeProvider() {
        return [
            [new Route(
                new GetMapping(
                    '/handler-string',
                ),
                new ResponseRequestHandlerStub(new Response()),
                []
            ), "GET\t/handler-string\t\t" . ResponseRequestHandlerStub::class],
        ];
    }

    #[DataProvider('routeProvider')]
    public function testRouteToString(Route $route, $expected) {
        self::assertSame($expected, $route->toString());
    }
}
