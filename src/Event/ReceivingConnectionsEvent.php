<?php

namespace Cspray\Labrador\Http\Event;

use Amp\Http\Server\HttpServer;
use Cspray\Labrador\AsyncEvent\Event;
use Cspray\Labrador\Http\ApplicationEvent;
use DateTimeImmutable;

class ReceivingConnectionsEvent implements Event {

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