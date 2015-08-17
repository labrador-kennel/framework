<?php

/**
 * 
 * @license See LICENSE in source root
 * @version 1.0
 * @since   1.0
 */

namespace Cspray\Labrador\Http\Test\Router;

use Cspray\Labrador\Http\Router\CallableResolver;
use PHPUnit_Framework_TestCase as UnitTestCase;

class CallableHandlerResolverTest extends UnitTestCase {

    function testHandlerIsCallableReturnsHandler() {
        $resolver = new CallableResolver();
        $closure = function() {};

        $this->assertSame($closure, $resolver->resolve($closure));
    }

    function testHandlerIsNotCallableReturnsFalse() {
        $resolver = new CallableResolver();

        $this->assertFalse($resolver->resolve('not_callable#action'));
    }

} 
