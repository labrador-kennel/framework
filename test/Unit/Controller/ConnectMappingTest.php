<?php

namespace Labrador\Test\Unit\Controller;

use Labrador\Web\HttpMethod;
use Labrador\Web\Router\ConnectMapping;

final class ConnectMappingTest extends RequestMappingTestCase {

    protected function getExpectedHttpMethod() : HttpMethod {
        return HttpMethod::Connect;
    }

    protected function getSubjectClass() : string {
        return ConnectMapping::class;
    }
}