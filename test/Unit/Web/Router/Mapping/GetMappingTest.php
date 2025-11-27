<?php

namespace Labrador\Test\Unit\Web\Router\Mapping;

use Labrador\Web\HttpMethod;
use Labrador\Web\Router\Mapping\GetMapping;

final class GetMappingTest extends RequestMappingTestCase {

    protected function getExpectedHttpMethod() : HttpMethod {
        return HttpMethod::Get;
    }

    protected function getSubjectClass() : string {
        return GetMapping::class;
    }
}
