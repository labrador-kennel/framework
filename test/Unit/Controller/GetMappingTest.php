<?php

namespace Labrador\Test\Unit\Controller;

use Labrador\Web\HttpMethod;
use Labrador\Web\Router\GetMapping;

final class GetMappingTest extends RequestMappingTestCase {

    protected function getExpectedHttpMethod() : HttpMethod {
        return HttpMethod::Get;
    }

    protected function getSubjectClass() : string {
        return GetMapping::class;
    }
}