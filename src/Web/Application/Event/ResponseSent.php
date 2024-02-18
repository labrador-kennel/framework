<?php

namespace Labrador\Web\Application\Event;

use Amp\Http\Server\Response;
use DateTimeImmutable;
use Labrador\AsyncEvent\Event;
use Labrador\Web\Application\ApplicationEvent;
use Labrador\Web\RequestAttribute;
use Ramsey\Uuid\UuidInterface;

final class ResponseSent implements Event {

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