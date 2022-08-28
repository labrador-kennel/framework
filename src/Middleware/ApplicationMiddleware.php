<?php

namespace Cspray\Labrador\Http\Middleware;

use Cspray\AnnotatedContainer\Attribute\ServiceAttribute;

#[\Attribute(\Attribute::TARGET_CLASS)]
final class ApplicationMiddleware implements ServiceAttribute {

    public function __construct(
        private readonly Priority $priority = Priority::Low,
        private readonly array $profiles = []
    ) {}

    public function getPriority() : Priority {
        return $this->priority;
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