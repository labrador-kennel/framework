<?php

namespace Labrador\Http\Test\Unit\Controller;

use Labrador\Http\HttpMethod;
use Labrador\Http\Router\GetMapping;

final class GetMappingTest extends RequestMappingTestCase {

    protected function getExpectedHttpMethod() : HttpMethod {
        return HttpMethod::Get;
    }

    protected function getSubjectClass() : string {
        return GetMapping::class;
    }
}