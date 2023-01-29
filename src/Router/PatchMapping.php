<?php

namespace Labrador\Http\Router;

use Labrador\Http\HttpMethod;

final class PatchMapping implements RequestMapping {

    public function __construct(
        private readonly string $path
    ) {}

    public function getHttpMethod() : HttpMethod {
        return HttpMethod::Patch;
    }

    public function getPath() : string {
        return $this->path;
    }

    public function withPath(string $path) : RequestMapping {
        return new self($path);
    }
}