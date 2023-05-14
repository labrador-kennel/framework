<?php

namespace Labrador\Web\Router;

enum RoutingResolutionReason {
    case RequestMatched;
    case NotFound;
    case MethodNotAllowed;
}
