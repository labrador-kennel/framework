<?php declare(strict_types=1);

namespace Labrador\Http\Event;

use Labrador\AsyncEvent\Event;
use Labrador\Http\Application;
use Labrador\Http\ApplicationEvent;
use DateTimeImmutable;

class ApplicationStarted implements Event {

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