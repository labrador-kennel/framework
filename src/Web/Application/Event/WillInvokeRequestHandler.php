<?php

namespace Labrador\Web\Application\Event;

use Amp\Http\Server\Request;
use Amp\Http\Server\RequestHandler;
use DateTimeImmutable;
use Labrador\AsyncEvent\Event;
use Labrador\Web\Application\ApplicationEvent;

/**
 * @implements Event<RequestHandlerAndRequest>
 */
final class WillInvokeRequestHandler implements Event {

    private readonly DateTimeImmutable $createdAt;

    public function __construct(
        private readonly RequestHandler $requestHandler,
        private readonly Request $request,
        DateTimeImmutable $createdAt = null
    ) {
        $this->createdAt = $createdAt ?? new DateTimeImmutable();
    }

    public function name() : string {
        return ApplicationEvent::WillInvokeRequestHandler->value;
    }

    public function payload() : RequestHandlerAndRequest {
        return new class($this->requestHandler, $this->request) implements RequestHandlerAndRequest {

            public function __construct(
                private readonly RequestHandler $requestHandler,
                private readonly Request $request,
            ) {
            }

            public function requestHandler() : RequestHandler {
                return $this->requestHandler;
            }

            public function request() : Request {
                return $this->request;
            }
        };
    }

    public function triggeredAt() : DateTimeImmutable {
        return $this->createdAt;
    }
}
