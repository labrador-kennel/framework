<?php

namespace Labrador\Http\Event;

use Labrador\AsyncEvent\Event;
use Labrador\AsyncEvent\StandardEvent;
use Labrador\Http\ApplicationEvent;
use Labrador\Http\Router\Router;
use DateTimeImmutable;

final class AddRoutes implements Event {

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