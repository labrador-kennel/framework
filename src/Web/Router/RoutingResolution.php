<?php

namespace Labrador\Web\Router;

use Labrador\Web\Controller\Controller;

final class RoutingResolution {

    public function __construct(
        public readonly ?Controller $controller,
        public readonly RoutingResolutionReason $reason
    ) {
    }
}
