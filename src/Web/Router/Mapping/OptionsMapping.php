<?php

namespace Labrador\Web\Router\Mapping;

use Labrador\Web\HttpMethod;

class OptionsMapping implements RequestMapping {

    public function __construct(
        private readonly string $path
    ) {}

    public function getHttpMethod() : HttpMethod {
        return HttpMethod::Options;
    }

    public function getPath() : string {
        return $this->path;
    }

    public function withPath(string $path) : RequestMapping {
        return new self($path);
    }
}