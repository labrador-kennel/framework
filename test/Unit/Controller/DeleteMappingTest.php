<?php

namespace Labrador\Http\Test\Unit\Controller;

use Labrador\Http\HttpMethod;
use Labrador\Http\Router\DeleteMapping;

final class DeleteMappingTest extends RequestMappingTestCase {

    protected function getExpectedHttpMethod() : HttpMethod {
        return HttpMethod::Delete;
    }

    protected function getSubjectClass() : string {
        return DeleteMapping::class;
    }
}