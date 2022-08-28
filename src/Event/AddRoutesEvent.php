<?php

namespace Cspray\Labrador\Http\Event;

use Cspray\Labrador\AsyncEvent\Event;
use Cspray\Labrador\AsyncEvent\StandardEvent;
use Cspray\Labrador\Http\ApplicationEvent;
use Cspray\Labrador\Http\Router\Router;
use DateTimeImmutable;

final class AddRoutesEvent implements Event {

    private readonly DateTimeImmutable $createdAt;

    public function __construct(
        private readonly Router $router,
        DateTimeImmutable $createdAt = null
    ) {
        $this->createdAt = $createdAt ?? new DateTimeImmutable();
    }

    public function getName() : string {
        return ApplicationEvent::AddRoutes->value;
    }

    public function getTarget() : Router {
        return $this->router;
    }

    public function getData() : array {
        return [];
    }

    public function getCreatedAt() : DateTimeImmutable {
        return $this->createdAt;
    }
}