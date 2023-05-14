<?php

namespace Labrador\Web\Controller;

use Amp\Http\Server\Middleware;
use Labrador\Router\MethodAndPathRequestMapping;
use Labrador\Web\Router\GetMapping;
use Labrador\Web\Router\RequestMapping;

#[\Attribute(\Attribute::TARGET_METHOD)]
final class Get implements RouteMappingAttribute {

    private readonly RequestMapping $requestMapping;

    public function __construct(
        private readonly string $path,
        /**
         * @var list<class-string<Middleware>> $middleware
         */
        private readonly array $middleware = []
    ) {
        $this->requestMapping = new GetMapping($this->path);
    }


    public function getMiddleware() : array {
        return $this->middleware;
    }

    public function getRequestMapping() : RequestMapping {
        return $this->requestMapping;
    }
}