<?php

namespace Labrador\Http\Controller;

use Amp\Http\Server\Middleware;
use Attribute;
use Cspray\AnnotatedContainer\Attribute\ServiceAttribute;
use Labrador\Http\HttpMethod;

#[Attribute(Attribute::TARGET_CLASS)]
final class HttpController implements ServiceAttribute, RouteMappingAttribute {

    public function __construct(
        private readonly HttpMethod $method,
        private readonly string $pattern,
        /**
         * @var list<class-string<Middleware>> $middleware
         */
        private readonly array $middleware = [],
        /**
         * @var list<string> $profiles
         */
        private readonly array $profiles = []
    ) {}

    public function getHttpMethod() : HttpMethod {
        return $this->method;
    }

    public function getPath() : string {
        return $this->pattern;
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