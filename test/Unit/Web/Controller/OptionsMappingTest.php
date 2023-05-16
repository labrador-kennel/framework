<?php

namespace Labrador\Test\Unit\Web\Controller;

use Labrador\Web\HttpMethod;
use Labrador\Web\Router\OptionsMapping;

final class OptionsMappingTest extends RequestMappingTestCase {

    protected function getExpectedHttpMethod() : HttpMethod {
        return HttpMethod::Options;
    }

    protected function getSubjectClass() : string {
        return OptionsMapping::class;
    }
}