<?php

namespace Labrador\Http\Test\Unit\Router;

use Labrador\Http\ContentType;
use Labrador\Http\HttpMethod;
use Labrador\Http\Router\MethodAndPathRequestMapping;
use PHPUnit\Framework\TestCase;

class RequestMappingTest extends TestCase {

    public function testCreateFromMethodAndPath() : void {
        $subject = MethodAndPathRequestMapping::fromMethodAndPath(HttpMethod::Get, '/foo');

        self::assertSame(HttpMethod::Get, $subject->getHttpMethod());
        self::assertSame('/foo', $subject->getPath());
    }

    public function testWithMethodImmutable() : void {
        $a = MethodAndPathRequestMapping::fromMethodAndPath(HttpMethod::Get, '/foo');
        $b = $a->withPath('/foo/bar');

        self::assertSame('/foo', $a->getPath());
        self::assertSame('/foo/bar', $b->getPath());
    }

}