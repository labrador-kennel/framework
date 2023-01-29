<?php

namespace Labrador\Http\Test\Unit\Controller;

use Labrador\Http\HttpMethod;
use Labrador\Http\Router\PatchMapping;

class PatchMappingTest extends RequestMappingTestCase {

    protected function getExpectedHttpMethod() : HttpMethod {
        return HttpMethod::Patch;
    }

    protected function getSubjectClass() : string {
        return PatchMapping::class;
    }
}