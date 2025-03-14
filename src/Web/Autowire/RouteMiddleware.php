<?php

namespace Labrador\Web\Autowire;

use Attribute;
use Cspray\AnnotatedContainer\Attribute\ServiceAttribute;

#[Attribute(Attribute::TARGET_CLASS)]
final class RouteMiddleware implements ServiceAttribute {

    public function __construct(
        /**
         * @var list<non-empty-string> $profiles
         */
        private readonly array $profiles = []
    ) {
    }

    /**
     * @return list<non-empty-string>
     */
    public function profiles() : array {
        return $this->profiles;
    }

    public function isPrimary() : bool {
        return false;
    }

    public function name() : ?string {
        return null;
    }
}
