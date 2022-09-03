<?php declare(strict_types=1);


namespace Labrador\Http\Event;

use Labrador\AsyncEvent\Event;
use Labrador\AsyncEvent\StandardEvent;
use Labrador\Http\AmpApplication;
use Labrador\Http\Application;
use Labrador\Http\ApplicationEvent;
use DateTimeImmutable;

class ApplicationStoppedEvent implements Event {

    private readonly DateTimeImmutable $createdAt;

    public function __construct(
        private readonly Application $app,
        DateTimeImmutable               $createdAt = null
    ) {
        $this->createdAt = $createdAt ?? new DateTimeImmutable();
    }

    public function getName() : string {
        return ApplicationEvent::ApplicationStopped->value;
    }

    public function getTarget() : Application {
        return $this->app;
    }

    public function getData() : array {
        return [];
    }

    public function getCreatedAt() : DateTimeImmutable {
        return $this->createdAt;
    }
}
