<?php

namespace Labrador\Web\Application\Event;

use DateTimeImmutable;
use Labrador\AsyncEvent\Event;
use Labrador\Web\Application\ApplicationEvent;
use Labrador\Web\Router\Router;

/**
 * @implements Event<Router>
 */
final class AddRoutes implements Event {

    private readonly DateTimeImmutable $createdAt;

    public function __construct(
        private readonly Router $router,
        DateTimeImmutable $createdAt = null
    ) {
        $this->createdAt = $createdAt ?? new DateTimeImmutable();
    }

    public function name() : string {
        return ApplicationEvent::AddRoutes->value;
    }

    public function payload() : Router {
        return $this->router;
    }

    public function triggeredAt() : DateTimeImmutable {
        return $this->createdAt;
    }
}
