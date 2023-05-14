<?php

namespace Labrador\Web\Router;

use Labrador\Web\HttpMethod;

class ConnectMapping implements RequestMapping {

    public function __construct(
        private readonly string $path
    ) {}

    public function getHttpMethod() : HttpMethod {
        return HttpMethod::Connect;
    }

    public function getPath() : string {
        return $this->path;
    }

    public function withPath(string $path) : RequestMapping {
        return new self($path);
    }
}