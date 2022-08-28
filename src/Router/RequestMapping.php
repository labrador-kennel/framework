<?php

namespace Cspray\Labrador\Http\Router;

use Cspray\Labrador\Http\ContentType;
use Cspray\Labrador\Http\HttpMethod;

final class RequestMapping {

    private function __construct(
        public readonly HttpMethod $method,
        public readonly string $pathPattern,
        /**
         * @var list<ContentType|string> $consumableContentTypes
         */
        public readonly array $consumableContentTypes,
        /**
         * @var list<ContentType|string> $producedContentTypes
         */
        public readonly array $producedContentTypes
    ) {}

    public static function fromMethodAndPath(HttpMethod $method, string $path) : self {
        return new self($method, $path, [ContentType::All], [ContentType::All]);
    }

    public function withPath(string $path) : self {
        return new self(
            $this->method,
            $path,
            $this->consumableContentTypes,
            $this->producedContentTypes
        );
    }

}