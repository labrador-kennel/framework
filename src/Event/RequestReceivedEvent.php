<?php

namespace Labrador\Http\Event;

use Amp\Http\Server\Request;
use Labrador\AsyncEvent\Event;
use Labrador\Http\ApplicationEvent;
use DateTimeImmutable;

final class RequestReceivedEvent implements Event {

    private readonly DateTimeImmutable $createdAt;

    public function __construct(
        private readonly Request $request,
        DateTimeImmutable $createdAt = null
    ) {
        $this->createdAt = $createdAt ?? new DateTimeImmutable();
    }

    public function getName() : string {
        return ApplicationEvent::RequestReceived->value;
    }

    public function getTarget() : Request {
        return $this->request;
    }

    public function getData() : array {
        return [];
    }

    public function getCreatedAt() : DateTimeImmutable {
        return $this->createdAt;
    }
}