<?php

namespace Labrador\Test\Unit\Controller;

use Labrador\Web\HttpMethod;
use Labrador\Web\Router\PostMapping;

final class PostMappingTest extends RequestMappingTestCase {

    protected function getExpectedHttpMethod() : HttpMethod {
        return HttpMethod::Post;
    }

    protected function getSubjectClass() : string {
        return PostMapping::class;
    }
}