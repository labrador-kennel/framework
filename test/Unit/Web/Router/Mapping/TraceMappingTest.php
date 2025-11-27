<?php

namespace Labrador\Test\Unit\Web\Router\Mapping;

use Labrador\Web\HttpMethod;
use Labrador\Web\Router\Mapping\TraceMapping;

final class TraceMappingTest extends RequestMappingTestCase {

    protected function getExpectedHttpMethod() : HttpMethod {
        return HttpMethod::Trace;
    }

    protected function getSubjectClass() : string {
        return TraceMapping::class;
    }
}
