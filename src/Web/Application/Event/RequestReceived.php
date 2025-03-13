<?php

namespace Labrador\Web\Application\Event;

use Amp\Http\Server\Request;
use DateTimeImmutable;
use Labrador\AsyncEvent\Event;
use Labrador\Web\Application\ApplicationEvent;

/**
 * @implements Event<Request>
 */
final class RequestReceived implements Event {

    private readonly DateTimeImmutable $createdAt;

    public function __construct(
        private readonly Request $request,
        DateTimeImmutable $createdAt = null
    ) {
        $this->createdAt = $createdAt ?? new DateTimeImmutable();
    }

    public function name() : string {
        return ApplicationEvent::RequestReceived->value;
    }

    public function payload() : Request {
        return $this->request;
    }

    public function triggeredAt() : DateTimeImmutable {
        return $this->createdAt;
    }
}