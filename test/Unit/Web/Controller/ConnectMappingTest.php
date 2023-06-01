<?php

namespace Labrador\Test\Unit\Web\Controller;

use Labrador\Web\HttpMethod;
use Labrador\Web\Router\Mapping\ConnectMapping;

final class ConnectMappingTest extends RequestMappingTestCase {

    protected function getExpectedHttpMethod() : HttpMethod {
        return HttpMethod::Connect;
    }

    protected function getSubjectClass() : string {
        return ConnectMapping::class;
    }
}