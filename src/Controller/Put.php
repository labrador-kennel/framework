<?php

namespace Labrador\Http\Controller;

use Amp\Http\Server\Middleware;
use Labrador\Http\HttpMethod;
use Labrador\Http\Router\MethodAndPathRequestMapping;
use Labrador\Http\Router\PutMapping;
use Labrador\Http\Router\RequestMapping;

#[\Attribute(\Attribute::TARGET_METHOD)]
final class Put implements RouteMappingAttribute {

    private readonly RequestMapping $requestMapping;

    public function __construct(
        private readonly string $path,
        /**
         * @var list<class-string<Middleware>> $middleware
         */
        private readonly array $middleware = []
    ) {
        $this->requestMapping = new PutMapping($this->path);
    }

    public function getMiddleware() : array {
        return $this->middleware;
    }

    public function getRequestMapping() : RequestMapping {
        return $this->requestMapping;
    }
}