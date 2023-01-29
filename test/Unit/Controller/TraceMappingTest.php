<?php

namespace Labrador\Http\Test\Unit\Controller;

use Labrador\Http\HttpMethod;
use Labrador\Http\Router\TraceMapping;

final class TraceMappingTest extends RequestMappingTestCase {

    protected function getExpectedHttpMethod() : HttpMethod {
        return HttpMethod::Trace;
    }

    protected function getSubjectClass() : string {
        return TraceMapping::class;
    }
}