<?php

namespace Labrador\Web\Router\Mapping;

use Labrador\Web\HttpMethod;

final class PutMapping implements RequestMapping {

    public function __construct(
        private readonly string $path
    ) {
    }

    public function getHttpMethod() : HttpMethod {
        return HttpMethod::Put;
    }

    public function getPath() : string {
        return $this->path;
    }

    public function withPath(string $path) : RequestMapping {
        return new self($path);
    }
}
