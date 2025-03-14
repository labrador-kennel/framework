<?php

namespace Labrador\Test\Unit\Web\Controller;

use Labrador\Web\HttpMethod;
use Labrador\Web\Router\Mapping\PutMapping;

final class PutMappingTest extends RequestMappingTestCase {

    protected function getExpectedHttpMethod() : HttpMethod {
        return HttpMethod::Put;
    }

    protected function getSubjectClass() : string {
        return PutMapping::class;
    }
}
