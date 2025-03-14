<?php

namespace Labrador\Test\Unit\Web\Controller;

use Labrador\Web\HttpMethod;
use Labrador\Web\Router\Mapping\PatchMapping;

class PatchMappingTest extends RequestMappingTestCase {

    protected function getExpectedHttpMethod() : HttpMethod {
        return HttpMethod::Patch;
    }

    protected function getSubjectClass() : string {
        return PatchMapping::class;
    }
}
