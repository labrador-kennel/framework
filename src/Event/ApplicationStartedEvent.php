<?php declare(strict_types=1);

namespace Cspray\Labrador\Http\Event;

use Cspray\Labrador\AsyncEvent\Event;
use Cspray\Labrador\Http\Application;
use Cspray\Labrador\Http\ApplicationEvent;
use DateTimeImmutable;

class ApplicationStartedEvent implements Event {

    private readonly DateTimeImmutable $createdAt;

    public function __construct(
        private readonly Application $application,
        DateTimeImmutable               $createdAt = null
    ) {
        $this->createdAt = $createdAt ?? new DateTimeImmutable();
    }

    public function getTarget() : Application {
        return $this->application;
    }

    public function getName() : string {
        return ApplicationEvent::ApplicationStarted->value;
    }

    public function getData() : array {
        return [];
    }

    public function getCreatedAt() : DateTimeImmutable {
        return $this->createdAt;
    }
}
