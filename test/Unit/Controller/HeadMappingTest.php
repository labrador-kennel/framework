<?php

namespace Labrador\Http\Test\Unit\Controller;

use Labrador\Http\HttpMethod;
use Labrador\Http\Router\HeadMapping;

final class HeadMappingTest extends RequestMappingTestCase {

    protected function getExpectedHttpMethod() : HttpMethod {
        return HttpMethod::Head;
    }

    protected function getSubjectClass() : string {
        return HeadMapping::class;
    }
}