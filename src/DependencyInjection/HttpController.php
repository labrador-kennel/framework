<?php

namespace Cspray\Labrador\Http\DependencyInjection;

use Cspray\AnnotatedContainer\Attribute\ServiceAttribute;
use Cspray\Labrador\Http\Http\HttpMethod;

#[\Attribute(\Attribute::TARGET_CLASS)]
final class HttpController implements ServiceAttribute {

    public function __construct(
        private readonly HttpMethod $method,
        private readonly string $pattern,
        private readonly array $profiles = []
    ) {}

    public function getMethod() : HttpMethod {
        return $this->method;
    }

    public function getRoutePattern() : string {
        return $this->pattern;
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