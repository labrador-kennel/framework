<?php

namespace Labrador\Http\Test\Unit\Controller;

use Labrador\Http\HttpMethod;
use Labrador\Http\Router\ConnectMapping;

final class ConnectMappingTest extends RequestMappingTestCase {

    protected function getExpectedHttpMethod() : HttpMethod {
        return HttpMethod::Connect;
    }

    protected function getSubjectClass() : string {
        return ConnectMapping::class;
    }
}