<?php

namespace Cspray\Labrador\Http\Controller;

use Cspray\AnnotatedContainer\Attribute\ServiceAttribute;
use Cspray\Labrador\Http\HttpMethod;

#[\Attribute(\Attribute::TARGET_CLASS)]
final class HttpController implements ServiceAttribute {

    /**
     * @param HttpMethod $method
     * @param string $pattern
     * @param list<string> $profiles
     */
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