<?php

declare(strict_types = 1);

/**
 * @license See LICENSE file in project root
 */

namespace Cspray\Labrador\Http\Test\HandlerResolver;

use Auryn\Injector;
use Cspray\Labrador\Http\HandlerResolver\HandlerResolver;
use Cspray\Labrador\Http\HandlerResolver\InjectorExecutableResolver;
use PHPUnit_Framework_TestCase as UnitTestCase;
use Symfony\Component\HttpFoundation\Request;

class InjectorExecutableResolverTest extends UnitTestCase {

    public function testCallWithNoArguments() {
        $resolver = $this->getMock(HandlerResolver::class);
        $resolver->expects($this->once())
                 ->method('resolve')
                 ->willReturn(function() { return 'called'; });

        $injector = new Injector();
        $injectorResolver = new InjectorExecutableResolver($resolver, $injector);

        $subject = $injectorResolver->resolve(Request::create('/'), 'foo');

        $this->assertSame('called', $subject());
    }

    public function testCallWithRequest() {
        $resolver = $this->getMock(HandlerResolver::class);
        $resolver->expects($this->once())
            ->method('resolve')
            ->willReturn(function(Request $request) { return $request->getPathInfo(); });

        $injector = new Injector();
        $injectorResolver = new InjectorExecutableResolver($resolver, $injector);

        $subject = $injectorResolver->resolve(Request::create('/foo/bar'), 'foo');

        $this->assertSame('/foo/bar', $subject());
    }

    public function testCallWithReq() {
        $resolver = $this->getMock(HandlerResolver::class);
        $resolver->expects($this->once())
            ->method('resolve')
            ->willReturn(function(Request $req) { return $req->getPathInfo(); });

        $injector = new Injector();
        $injectorResolver = new InjectorExecutableResolver($resolver, $injector);

        $subject = $injectorResolver->resolve(Request::create('/foo/bar'), 'foo');

        $this->assertSame('/foo/bar', $subject());
    }

}