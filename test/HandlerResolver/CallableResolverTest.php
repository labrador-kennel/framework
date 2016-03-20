<?php

/**
 *
 * @license See LICENSE in source root
 * @version 1.0
 * @since   1.0
 */

namespace Cspray\Labrador\Http\Test\HandlerResolver;

use Cspray\Labrador\Http\HandlerResolver\CallableResolver;
use Zend\Diactoros\ServerRequest;
use PHPUnit_Framework_TestCase as UnitTestCase;

class CallableResolverTest extends UnitTestCase {

    private $request;

    public function setUp() {
        parent::setUp();
        $this->request = (new ServerRequest());
    }

    function testHandlerIsCallableReturnsHandler() {
        $resolver = new CallableResolver();
        $closure = function() {};

        $this->assertSame($closure, $resolver->resolve($this->request, $closure));
    }

    function testHandlerIsNotCallableReturnsFalse() {
        $resolver = new CallableResolver();

        $this->assertFalse($resolver->resolve($this->request, 'not_callable#action'));
    }

}
