<?php

namespace Labrador\Http\Test\Unit\Controller;

use Labrador\Http\HttpMethod;
use Labrador\Http\Router\PutMapping;

final class PutMappingTest extends RequestMappingTestCase {

    protected function getExpectedHttpMethod() : HttpMethod {
        return HttpMethod::Put;
    }

    protected function getSubjectClass() : string {
        return PutMapping::class;
    }
}