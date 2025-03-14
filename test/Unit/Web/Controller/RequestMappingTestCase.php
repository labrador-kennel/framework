<?php

namespace Labrador\Test\Unit\Web\Controller;

use Labrador\Web\HttpMethod;
use Labrador\Web\Router\Mapping\RequestMapping;
use PHPUnit\Framework\TestCase;

abstract class RequestMappingTestCase extends TestCase {

    abstract protected function getExpectedHttpMethod() : HttpMethod;

    /**
     * @return class-string<RequestMapping>
     */
    abstract protected function getSubjectClass() : string;

    public function testGetHttpMethod() : void {
        $subjectClass = $this->getSubjectClass();
        self::assertSame(
            $this->getExpectedHttpMethod(),
            (new $subjectClass('/path'))->getHttpMethod()
        );
    }

    public function testGetPath() : void {
        $subjectClass = $this->getSubjectClass();
        self::assertSame(
            '/some-url-path',
            (new $subjectClass('/some-url-path'))->getPath()
        );
    }

    public function testWithPathReturnsDifferentMapping() : void {
        $subjectClass = $this->getSubjectClass();
        $mapping = new $subjectClass('/path');
        self::assertNotSame(
            $mapping,
            $mapping->withPath('/another-path')
        );
    }

    public function testWithPathReturnsNewPath() : void {
        $subjectClass = $this->getSubjectClass();
        $mapping = (new $subjectClass('/path'))->withPath('/another-path');

        self::assertSame(
            '/another-path',
            $mapping->getPath()
        );
    }
}
