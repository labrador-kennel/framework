<?php

namespace Labrador\Http\Test\Unit\Controller;

use Labrador\Http\HttpMethod;
use Labrador\Http\Router\PostMapping;

final class PostMappingTest extends RequestMappingTestCase {

    protected function getExpectedHttpMethod() : HttpMethod {
        return HttpMethod::Post;
    }

    protected function getSubjectClass() : string {
        return PostMapping::class;
    }
}