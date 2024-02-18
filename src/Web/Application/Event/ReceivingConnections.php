<?php

namespace Labrador\Web\Application\Event;

use Amp\Http\Server\HttpServer;
use DateTimeImmutable;
use Labrador\AsyncEvent\Event;
use Labrador\Web\Application\ApplicationEvent;

class ReceivingConnections implements Event {

    private readonly DateTimeImmutable $createdAt;

    public function __construct(
        private readonly HttpServer $server,
        DateTimeImmutable $createdAt = null
    ) {
        $this->createdAt = $createdAt ?? new DateTimeImmutable();
    }

    public function getName() : string {
        return ApplicationEvent::ReceivingConnections->value;
    }

    public function getTarget() : HttpServer {
        return $this->server;
    }

    public function getData() : array {
        return [];
    }

    public function getCreatedAt() : DateTimeImmutable {
        return $this->createdAt;
    }
}