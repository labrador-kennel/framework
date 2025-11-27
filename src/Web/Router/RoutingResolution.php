<?php

namespace Labrador\Web\Router;

use Amp\Http\Server\Middleware;
use Amp\Http\Server\RequestHandler;

final class RoutingResolution {

    /**
     * @param RequestHandler|null $requestHandler
     * @param list<Middleware> $middleware
     * @param RoutingResolutionReason $reason
     */
    public function __construct(
        public readonly ?RequestHandler $requestHandler,
        public readonly array $middleware,
        public readonly RoutingResolutionReason $reason
    ) {
    }
}
