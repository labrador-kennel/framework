<?php

/**
 *
 * @license See LICENSE in source root
 * @version 1.0
 * @since   1.0
 */

namespace Labrador\Test\Unit\Web\Router;

use Labrador\Test\Unit\Web\Stub\ToStringControllerStub;
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
                new ToStringControllerStub('handler_name')
            ), "GET\t/handler-string\t\thandler_name"],
        ];
    }

    #[DataProvider('routeProvider')]
    public function testRouteToString(Route $route, $expected) {
        self::assertSame($expected, $route->toString());
    }
}
