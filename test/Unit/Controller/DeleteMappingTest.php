<?php

namespace Labrador\Test\Unit\Controller;

use Labrador\Web\HttpMethod;
use Labrador\Web\Router\DeleteMapping;

final class DeleteMappingTest extends RequestMappingTestCase {

    protected function getExpectedHttpMethod() : HttpMethod {
        return HttpMethod::Delete;
    }

    protected function getSubjectClass() : string {
        return DeleteMapping::class;
    }
}