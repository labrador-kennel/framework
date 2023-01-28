<?php

namespace Labrador\Http\Router;

use Labrador\Http\ContentType;
use Labrador\Http\HttpMethod;

final class MethodAndPathRequestMapping implements RequestMapping {

    private function __construct(
        private readonly HttpMethod $method,
        private readonly string $pathPattern
    ) {}

    public static function fromMethodAndPath(HttpMethod $method, string $path) : self {
        return new self($method, $path);
    }

    public function withPath(string $path) : self {
        return new self(
            $this->method,
            $path,
        );
    }

    public function getHttpMethod() : HttpMethod {
        return $this->method;
    }

    public function getPath() : string {
        return $this->pathPattern;
    }
}