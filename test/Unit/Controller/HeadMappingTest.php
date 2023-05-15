<?php

namespace Labrador\Test\Unit\Controller;

use Labrador\Web\HttpMethod;
use Labrador\Web\Router\HeadMapping;

final class HeadMappingTest extends RequestMappingTestCase {

    protected function getExpectedHttpMethod() : HttpMethod {
        return HttpMethod::Head;
    }

    protected function getSubjectClass() : string {
        return HeadMapping::class;
    }
}