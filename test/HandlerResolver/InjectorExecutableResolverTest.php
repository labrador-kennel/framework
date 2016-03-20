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
use Psr\Http\Message\ServerRequestInterface;
use Zend\Diactoros\ServerRequest as Request;
use Zend\Diactoros\Uri;

class InjectorExecutableResolverTest extends UnitTestCase {

    public function testCallWithNoArguments() {
        $resolver = $this->getMock(HandlerResolver::class);
        $resolver->expects($this->once())
                 ->method('resolve')
                 ->willReturn(function() { return 'called'; });

        $injector = new Injector();
        $injectorResolver = new InjectorExecutableResolver($resolver, $injector);

        $request = (new Request())->withMethod('GET')->withUri(new Uri('/'));
        $subject = $injectorResolver->resolve($request, 'foo');

        $this->assertSame('called', $subject());
    }

    public function testCallWithRequest() {
        $resolver = $this->getMock(HandlerResolver::class);
        $resolver->expects($this->once())
            ->method('resolve')
            ->willReturn(function(ServerRequestInterface $req) { return $req->getUri()->getPath(); });

        $injector = new Injector();
        $injectorResolver = new InjectorExecutableResolver($resolver, $injector);

        $request = (new Request())->withMethod('GET')->withUri(new Uri('/foo/bar'));
        $subject = $injectorResolver->resolve($request, 'foo');

        $this->assertSame('/foo/bar', $subject());
    }

    public function testCallWithReq() {
        $resolver = $this->getMock(HandlerResolver::class);
        $resolver->expects($this->once())
            ->method('resolve')
            ->willReturn(function(ServerRequestInterface $req) { return $req->getUri()->getPath(); });

        $injector = new Injector();
        $injectorResolver = new InjectorExecutableResolver($resolver, $injector);

        $request = (new Request())->withMethod('GET')->withUri(new Uri('/foo/bar'));
        $subject = $injectorResolver->resolve($request, 'foo');

        $this->assertSame('/foo/bar', $subject());
    }

}