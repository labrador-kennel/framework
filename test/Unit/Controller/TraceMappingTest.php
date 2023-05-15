<?php

namespace Labrador\Test\Unit\Controller;

use Labrador\Web\HttpMethod;
use Labrador\Web\Router\TraceMapping;

final class TraceMappingTest extends RequestMappingTestCase {

    protected function getExpectedHttpMethod() : HttpMethod {
        return HttpMethod::Trace;
    }

    protected function getSubjectClass() : string {
        return TraceMapping::class;
    }
}