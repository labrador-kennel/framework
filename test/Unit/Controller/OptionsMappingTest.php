<?php

namespace Labrador\Http\Test\Unit\Controller;

use Labrador\Http\HttpMethod;
use Labrador\Http\Router\OptionsMapping;

final class OptionsMappingTest extends RequestMappingTestCase {

    protected function getExpectedHttpMethod() : HttpMethod {
        return HttpMethod::Options;
    }

    protected function getSubjectClass() : string {
        return OptionsMapping::class;
    }
}