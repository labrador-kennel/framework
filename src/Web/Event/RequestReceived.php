<?php

namespace Labrador\Web\Event;

use Amp\Http\Server\Request;
use DateTimeImmutable;
use Labrador\AsyncEvent\Event;
use Labrador\Web\Application\ApplicationEvent;

final class RequestReceived implements Event {

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