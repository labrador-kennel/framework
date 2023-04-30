<?php declare(strict_types=1);


namespace Labrador\Http\Event;

use DateTimeImmutable;
use Labrador\AsyncEvent\Event;
use Labrador\Http\Application\Application;
use Labrador\Http\Application\ApplicationEvent;

class ApplicationStopped implements Event {

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
