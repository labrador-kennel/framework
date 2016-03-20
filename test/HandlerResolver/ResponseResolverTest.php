<?php

/**
 *
 * @license See LICENSE in source root
 * @version 1.0
 * @since   1.0
 */

namespace Cspray\Labrador\Http\Test\HandlerResolver;

use Cspray\Labrador\Http\HandlerResolver\ResponseResolver;
use PHPUnit_Framework_TestCase as UnitTestCase;
use Zend\Diactoros\ServerRequest as Request;
use Zend\Diactoros\Response;
use Zend\Diactoros\Uri;

class ResponseResolverTest extends UnitTestCase {

    function handlerReturnsFalseProvider() {
        return [
            ['string'],
            [1],
            [null],
            [1.1],
            [[]],
            [new \stdClass()],
            [true],
            [false]
        ];
    }

    /**
     * @dataProvider handlerReturnsFalseProvider
     */
    function testHandlerNotResponseReturnsFalse($handler) {
        $resolver = new ResponseResolver();
        $request = (new Request())->withMethod('GET')->withUri(new Uri('/'));
        $this->assertFalse($resolver->resolve($request, $handler));
    }

    function testHandlerResponseReturnsCallback() {
        $resolver = new ResponseResolver();
        $response = new Response();

        $request = (new Request())->withMethod('GET')->withUri(new Uri('/'));
        $controller = $resolver->resolve($request, $response);
        $this->assertTrue(is_callable($controller));
        $this->assertSame($response, $controller());
    }

}
