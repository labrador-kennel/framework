<?php

/**
 * 
 * @license See LICENSE in source root
 * @version 1.0
 * @since   1.0
 */

namespace Labrador\Http\Test\Unit\Router;

use Labrador\Http\Router\HandlerResolver;
use Labrador\Http\Router\ResolverChain;
use PHPUnit_Framework_TestCase as UnitTestCase;

class ResolverChainTest extends UnitTestCase {

    function testExecutingChainCorrectly() {
        $chain = new ResolverChain();

        $closure = function() {};
        $foo = $this->getMock(HandlerResolver::class);
        $foo->expects($this->once())->method('resolve')->will($this->returnValue(false));
        $bar = $this->getMock(HandlerResolver::class);
        $bar->expects($this->once())->method('resolve')->will($this->returnValue($closure));
        $qux = $this->getMock(HandlerResolver::class);
        $qux->expects($this->never())->method('resolve');

        $chain->add($foo)->add($bar)->add($qux);

        $this->assertSame($closure, $chain->resolve('handler'));
    }

    function testReturnFalseIfAllResolversFail() {
        $chain = new ResolverChain();

        $foo = $this->getMock(HandlerResolver::class);
        $foo->expects($this->once())->method('resolve')->will($this->returnValue(false));
        $bar = $this->getMock(HandlerResolver::class);
        $bar->expects($this->once())->method('resolve')->will($this->returnValue(false));
        $qux = $this->getMock(HandlerResolver::class);
        $qux->expects($this->once())->method('resolve')->will($this->returnValue(false));

        $chain->add($foo)->add($bar)->add($qux);

        $this->assertFalse($chain->resolve('handler'));
    }

} 
