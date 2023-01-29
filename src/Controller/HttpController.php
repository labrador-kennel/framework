<?php

namespace Labrador\Http\Controller;

use Amp\Http\Server\Middleware;
use Attribute;
use Cspray\AnnotatedContainer\Attribute\ServiceAttribute;
use Labrador\Http\HttpMethod;
use Labrador\Http\Router\RequestMapping;

#[Attribute(Attribute::TARGET_CLASS)]
final class HttpController implements ServiceAttribute, RouteMappingAttribute {

    public function __construct(
        private readonly RequestMapping $requestMapping,
        /**
         * @var list<class-string<Middleware>> $middleware
         */
        private readonly array $middleware = [],
        /**
         * @var list<string> $profiles
         */
        private readonly array $profiles = []
    ) {}

    public function getRequestMapping() : RequestMapping {
        return $this->requestMapping;
    }

    /**
     * @return list<class-string<Middleware>>
     */
    public function getMiddleware() : array {
        return $this->middleware;
    }

    public function getProfiles() : array {
        return $this->profiles;
    }

    public function isPrimary() : bool {
        return false;
    }

    public function getName() : ?string {
        return null;
    }
}