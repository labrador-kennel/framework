<?php

namespace Cspray\Labrador\Http\DependencyInjection;

use Cspray\AnnotatedContainer\Attribute\ServiceAttribute;

#[\Attribute(\Attribute::TARGET_CLASS)]
final class ControllerMiddleware implements ServiceAttribute {

    public function getProfiles() : array {
        // TODO: Implement getProfiles() method.
    }

    public function isPrimary() : bool {
        // TODO: Implement isPrimary() method.
    }

    public function getName() : ?string {
        // TODO: Implement getName() method.
    }
}