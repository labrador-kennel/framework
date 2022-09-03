<?php

namespace Labrador\Http\Middleware;

use Cspray\AnnotatedContainer\Attribute\ServiceAttribute;

#[\Attribute(\Attribute::TARGET_CLASS)]
final class ApplicationMiddleware implements ServiceAttribute {

    public function __construct(
        private readonly Priority $priority = Priority::Low,
        /**
         * @var list<string> $profiles
         */
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