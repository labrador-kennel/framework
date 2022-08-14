<?php

namespace Cspray\Labrador\Http\Event;

use Cspray\Labrador\AsyncEvent\Event;
use Cspray\Labrador\Http\ApplicationEvent;
use Cspray\Labrador\Http\Controller\Controller;
use Cspray\Labrador\Http\Http\RequestAttribute;
use DateTimeImmutable;
use Ramsey\Uuid\UuidInterface;

final class ControllerInvokedEvent implements Event {

    private readonly DateTimeImmutable $createdAt;

    public function __construct(
        private readonly Controller $controller,
        private readonly UuidInterface $requestId,
        DateTimeImmutable $createdAt = null
    ) {
        $this->createdAt = $createdAt ?? new DateTimeImmutable();
    }

    public function getName() : string {
        return ApplicationEvent::ControllerInvoked->value;
    }

    public function getTarget() : Controller {
        return $this->controller;
    }

    public function getData() : array {
        return [
            RequestAttribute::RequestId->value => $this->requestId
        ];
    }

    public function getCreatedAt() : DateTimeImmutable {
        return $this->createdAt;
    }
}