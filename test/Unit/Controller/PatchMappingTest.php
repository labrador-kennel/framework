<?php

namespace Labrador\Test\Unit\Controller;

use Labrador\Web\HttpMethod;
use Labrador\Web\Router\PatchMapping;

class PatchMappingTest extends RequestMappingTestCase {

    protected function getExpectedHttpMethod() : HttpMethod {
        return HttpMethod::Patch;
    }

    protected function getSubjectClass() : string {
        return PatchMapping::class;
    }
}