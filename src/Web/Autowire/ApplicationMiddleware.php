<?php

namespace Labrador\Web\Autowire;

use Cspray\AnnotatedContainer\Attribute\ServiceAttribute;
use Labrador\Web\Middleware\Priority;

#[\Attribute(\Attribute::TARGET_CLASS)]
final class ApplicationMiddleware implements ServiceAttribute {

    public function __construct(
        private readonly Priority $priority = Priority::Low,
        /**
         * @var list<non-empty-string> $profiles
         */
        private readonly array $profiles = []
    ) {}

    public function getPriority() : Priority {
        return $this->priority;
    }

    /**
     * @return list<non-empty-string>
     */
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