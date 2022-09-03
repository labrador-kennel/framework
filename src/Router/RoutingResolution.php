<?php

namespace Labrador\Http\Router;

use Labrador\Http\Controller\Controller;

final class RoutingResolution {

    public function __construct(
        public readonly ?Controller $controller,
        public readonly RoutingResolutionReason $reason
    ) {}

}