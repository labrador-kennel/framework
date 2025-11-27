<?php declare(strict_types=1);

namespace Labrador\Web\Application\Event;

use DateTimeImmutable;
use Labrador\AsyncEvent\Event;
use Labrador\Web\Application\ApplicationEvent;
use Labrador\Web\Middleware\GlobalMiddlewareCollection;

/**
 * @implements Event<GlobalMiddlewareCollection>
 */
final class AddGlobalMiddleware implements Event {

    private readonly DateTimeImmutable $triggeredAt;

    public function __construct(
        private readonly GlobalMiddlewareCollection $globalMiddlewareCollection,
        DateTimeImmutable $triggeredAt = null
    ) {
        $this->triggeredAt = $triggeredAt ?? new DateTimeImmutable();
    }

    public function name() : string {
        return ApplicationEvent::AddGlobalMiddleware->value;
    }

    public function payload() : GlobalMiddlewareCollection {
        return $this->globalMiddlewareCollection;
    }

    public function triggeredAt() : DateTimeImmutable {
        return $this->triggeredAt;
    }
}
