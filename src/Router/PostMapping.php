<?php

namespace Labrador\Http\Router;

use Labrador\Http\HttpMethod;

final class PostMapping implements RequestMapping {

    public function __construct(
        private readonly string $path
    ) {}

    public function getHttpMethod() : HttpMethod {
        return HttpMethod::Post;
    }

    public function getPath() : string {
        return $this->path;
    }

    public function withPath(string $path) : RequestMapping {
        return new self($path);
    }
}