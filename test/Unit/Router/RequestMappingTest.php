<?php

namespace Labrador\Http\Test\Unit\Router;

use Labrador\Http\ContentType;
use Labrador\Http\HttpMethod;
use Labrador\Http\Router\RequestMapping;
use PHPUnit\Framework\TestCase;

class RequestMappingTest extends TestCase {

    public function testCreateFromMethodAndPath() : void {
        $subject = RequestMapping::fromMethodAndPath(HttpMethod::Get, '/foo');

        self::assertSame(HttpMethod::Get, $subject->method);
        self::assertSame('/foo', $subject->pathPattern);
    }

    public function testWithMethodImmutable() : void {
        $a = RequestMapping::fromMethodAndPath(HttpMethod::Get, '/foo');
        $b = $a->withPath('/foo/bar');

        self::assertSame('/foo', $a->pathPattern);
        self::assertSame('/foo/bar', $b->pathPattern);
    }

}