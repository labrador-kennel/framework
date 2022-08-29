<?php

namespace Cspray\Labrador\Http\Controller\Dto;

use Amp\Http\Server\Middleware;
use Attribute;
use Cspray\Labrador\Http\Controller\RouteMappingAttribute;
use Cspray\Labrador\Http\HttpMethod;

#[Attribute(Attribute::TARGET_METHOD)]
final class Delete implements RouteMappingAttribute {

    public function __construct(
        private readonly string $path,
        /**
         * @var list<class-string<Middleware>> $middleware
         */
        private readonly array $middleware = []
    ) {}

    public function getHttpMethod() : HttpMethod {
        return HttpMethod::Delete;
    }

    public function getPath() : string {
        return $this->path;
    }

    public function getMiddleware() : array {
        return $this->middleware;
    }
}