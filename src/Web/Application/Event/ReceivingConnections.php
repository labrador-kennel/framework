<?php

namespace Labrador\Web\Application\Event;

use Amp\Http\Server\HttpServer;
use DateTimeImmutable;
use Labrador\AsyncEvent\Event;
use Labrador\Web\Application\ApplicationEvent;

/**
 * @implements Event<HttpServer>
 */
final class ReceivingConnections implements Event {

    private readonly DateTimeImmutable $createdAt;

    public function __construct(
        private readonly HttpServer $server,
        DateTimeImmutable $createdAt = null
    ) {
        $this->createdAt = $createdAt ?? new DateTimeImmutable();
    }

    public function name() : string {
        return ApplicationEvent::ReceivingConnections->value;
    }

    public function payload() : HttpServer {
        return $this->server;
    }

    public function triggeredAt() : DateTimeImmutable {
        return $this->createdAt;
    }
}