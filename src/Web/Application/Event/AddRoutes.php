<?php

namespace Labrador\Web\Application\Event;

use DateTimeImmutable;
use Labrador\AsyncEvent\Event;
use Labrador\Web\Application\ApplicationEvent;
use Labrador\Web\Middleware\GlobalMiddlewareCollection;
use Labrador\Web\Router\Router;

/**
 * @implements Event<RouterAndGlobalMiddlewareCollection>
 */
final class AddRoutes implements Event {

    private readonly DateTimeImmutable $createdAt;

    public function __construct(
        private readonly Router $router,
        private readonly GlobalMiddlewareCollection $globalMiddlewareCollection,
        DateTimeImmutable $createdAt = null
    ) {
        $this->createdAt = $createdAt ?? new DateTimeImmutable();
    }

    public function name() : string {
        return ApplicationEvent::AddRoutes->value;
    }

    public function payload() : RouterAndGlobalMiddlewareCollection {
        return new class($this->router, $this->globalMiddlewareCollection) implements RouterAndGlobalMiddlewareCollection {
            public function __construct(
                private readonly Router $router,
                private readonly GlobalMiddlewareCollection $globalMiddleware,
            ) {
            }

            public function router() : Router {
                return $this->router;
            }

            public function globalMiddleware() : GlobalMiddlewareCollection {
                return $this->globalMiddleware;
            }
        };
    }

    public function triggeredAt() : DateTimeImmutable {
        return $this->createdAt;
    }
}
