<?php

namespace Labrador\Web\Application\Event;

use Amp\Http\Server\Request;
use Amp\Http\Server\Response;
use DateTimeImmutable;
use Labrador\AsyncEvent\Event;
use Labrador\Web\Application\ApplicationEvent;

/**
 * @implements Event<RequestAndResponse>
 */
final class ResponseSent implements Event {

    private readonly DateTimeImmutable $createdAt;

    public function __construct(
        private readonly Request $request,
        private readonly Response $response,
        DateTimeImmutable $createdAt = null
    ) {
        $this->createdAt = $createdAt ?? new DateTimeImmutable();
    }

    public function name() : string {
        return ApplicationEvent::ResponseSent->value;
    }

    public function payload() : RequestAndResponse {
        return new class($this->request, $this->response) implements RequestAndResponse {

            public function __construct(
                private readonly Request $request,
                private readonly Response $response
            ) {
            }

            public function request() : Request {
                return $this->request;
            }

            public function response() : Response {
                return $this->response;
            }
        };
    }

    public function triggeredAt() : DateTimeImmutable {
        return $this->createdAt;
    }
}
