<?php

namespace Labrador\Http\Controller;

use Attribute;
use Cspray\AnnotatedContainer\Attribute\ServiceAttribute;

#[Attribute(Attribute::TARGET_CLASS)]
final class ControllerActions implements ServiceAttribute {

    public function __construct(
        /**
         * @var list<string> $profiles
         */
        private readonly array $profiles = []
    ) {}

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