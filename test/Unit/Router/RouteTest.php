<?php

/**
 *
 * @license See LICENSE in source root
 * @version 1.0
 * @since   1.0
 */

namespace Labrador\Http\Test\Unit\Router;

use Labrador\Http\HttpMethod;
use Labrador\Http\Router\MethodAndPathRequestMapping;
use Labrador\Http\Router\Route;
use Labrador\Http\Test\Unit\Stub\ToStringControllerStub;
use PHPUnit\Framework\TestCase;

class RouteTest extends TestCase {

    public function routeProvider() {
        return [
            [new Route(
                MethodAndPathRequestMapping::fromMethodAndPath(
                    HttpMethod::Get,
                    '/handler-string',
                ),
                new ToStringControllerStub('handler_name')
            ), "GET\t/handler-string\t\thandler_name"],
        ];
    }

    /**
     * @dataProvider routeProvider
     */
    public function testRouteToString(Route $route, $expected) {
        self::assertSame($expected, $route->toString());
    }
}
