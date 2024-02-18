<?php

namespace Labrador\Web\Application\Event;

use DateTimeImmutable;
use Labrador\AsyncEvent\Event;
use Labrador\Web\Application\ApplicationEvent;
use Labrador\Web\Controller\Controller;
use Labrador\Web\RequestAttribute;
use Ramsey\Uuid\UuidInterface;

final class WillInvokeController implements Event {

    private readonly DateTimeImmutable $createdAt;

    public function __construct(
        private readonly Controller $controller,
        private readonly UuidInterface $requestId,
        DateTimeImmutable $createdAt = null
    ) {
        $this->createdAt = $createdAt ?? new DateTimeImmutable();
    }

    public function getName() : string {
        return ApplicationEvent::WillInvokeController->value;
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