<?php

/**
 *
 * @license See LICENSE in source root
 * @version 1.0
 * @since   1.0
 */

namespace Cspray\Labrador\Http\Test\HandlerResolver;

use Cspray\Labrador\Http\HandlerResolver\HandlerResolver;
use Cspray\Labrador\Http\HandlerResolver\ResolverChain;
use PHPUnit_Framework_TestCase as UnitTestCase;
use Zend\Diactoros\ServerRequest as Request;
use Zend\Diactoros\Uri;

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

        $request = (new Request())->withMethod('GET')->withUri(new Uri('/'));
        $this->assertSame($closure, $chain->resolve($request, 'handler'));
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

        $request = (new Request())->withMethod('GET')->withUri(new Uri('/'));
        $this->assertFalse($chain->resolve($request, 'handler'));
    }

}
