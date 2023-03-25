<?php declare(strict_types=1);

namespace Labrador\Http\Event;

use Amp\Future;
use Closure;
use Labrador\AsyncEvent\AbstractListenerProvider;
use Labrador\CompositeFuture\CompositeFuture;
use Labrador\Http\ApplicationEvent;

abstract class ApplicationStoppedListener extends AbstractListenerProvider {

    public function __construct() {
        parent::__construct(
            [ApplicationEvent::ApplicationStopped->value],
            $this->handle(...)
        );
    }

    abstract protected function handle(ApplicationStopped $applicationStopped) : Future|CompositeFuture|null;

}
