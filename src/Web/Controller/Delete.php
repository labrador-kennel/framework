<?php

namespace Labrador\Web\Controller;

use Amp\Http\Server\Middleware;
use Attribute;
use Labrador\Router\MethodAndPathRequestMapping;
use Labrador\Web\Router\DeleteMapping;
use Labrador\Web\Router\RequestMapping;

#[Attribute(Attribute::TARGET_METHOD)]
final class Delete implements RouteMappingAttribute {

    private readonly RequestMapping $requestMapping;

    public function __construct(
        private readonly string $path,
        /**
         * @var list<class-string<Middleware>> $middleware
         */
        private readonly array $middleware = []
    ) {
        $this->requestMapping = new DeleteMapping($this->path);
    }

    public function getMiddleware() : array {
        return $this->middleware;
    }

    public function getRequestMapping() : RequestMapping {
        return $this->requestMapping;
    }
}