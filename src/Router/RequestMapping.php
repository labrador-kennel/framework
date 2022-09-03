<?php

namespace Labrador\Http\Router;

use Labrador\Http\ContentType;
use Labrador\Http\HttpMethod;

final class RequestMapping {

    private function __construct(
        public readonly HttpMethod $method,
        public readonly string $pathPattern
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

}