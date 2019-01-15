<?php

/**
 * 
 * @license See LICENSE in source root
 * @version 1.0
 * @since   1.0
 */

namespace Cspray\Labrador\Http\Test\Router;

use Cspray\Labrador\Http\Router\Route;
use PHPUnit\Framework\TestCase;

class RouteTest extends TestCase {

    function routeProvider() {
        return [
            [new Route('/handler-string', 'GET', 'handler_name'), "GET\t/handler-string\t\thandler_name"],
        ];
    }

    /**
     * @dataProvider routeProvider
     */
    function testRouteToString($route, $expected) {
        $this->assertEquals($expected, (string) $route);
    }

}
