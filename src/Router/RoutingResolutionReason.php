<?php

namespace Cspray\Labrador\Http\Router;

enum RoutingResolutionReason {
    case RequestMatched;
    case NotFound;
    case MethodNotAllowed;
}
