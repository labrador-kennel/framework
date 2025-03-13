<?php declare(strict_types=1);


namespace Labrador\Web\Application\Event;

use DateTimeImmutable;
use Labrador\AsyncEvent\Event;
use Labrador\Web\Application\Application;
use Labrador\Web\Application\ApplicationEvent;

/**
 * @implements Event<Application>
 */
final class ApplicationStopped implements Event {

    private readonly DateTimeImmutable $createdAt;

    public function __construct(
        private readonly Application $app,
        DateTimeImmutable               $createdAt = null
    ) {
        $this->createdAt = $createdAt ?? new DateTimeImmutable();
    }

    public function name() : string {
        return ApplicationEvent::ApplicationStopped->value;
    }

    public function payload() : Application {
        return $this->app;
    }

    public function triggeredAt() : DateTimeImmutable {
        return $this->createdAt;
    }
}
