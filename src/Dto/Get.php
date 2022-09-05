<?php

namespace Labrador\Http\Dto;

use Amp\Http\Server\Middleware;
use Labrador\Http\Controller\RouteMappingAttribute;
use Labrador\Http\HttpMethod;

#[\Attribute(\Attribute::TARGET_METHOD)]
final class Get implements RouteMappingAttribute {

    public function __construct(
        private readonly string $path,
        /**
         * @var list<class-string<Middleware>> $middleware
         */
        private readonly array $middleware = []
    ) {}

    public function getHttpMethod() : HttpMethod {
        return HttpMethod::Get;
    }

    public function getPath() : string {
        return $this->path;
    }

    public function getMiddleware() : array {
        return $this->middleware;
    }
}