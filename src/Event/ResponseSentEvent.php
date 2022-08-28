<?php

namespace Cspray\Labrador\Http\Event;

use Amp\Http\Server\Response;
use Cspray\Labrador\AsyncEvent\Event;
use Cspray\Labrador\Http\ApplicationEvent;
use Cspray\Labrador\Http\RequestAttribute;
use DateTimeImmutable;
use Ramsey\Uuid\UuidInterface;

final class ResponseSentEvent implements Event {

    private readonly DateTimeImmutable $createdAt;

    public function __construct(
        private readonly Response $response,
        private readonly UuidInterface $requestId,
        DateTimeImmutable $createdAt = null
    ) {
        $this->createdAt = $createdAt ?? new DateTimeImmutable();
    }

    public function getName() : string {
        return ApplicationEvent::ResponseSent->value;
    }

    public function getTarget() : Response {
        return $this->response;
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