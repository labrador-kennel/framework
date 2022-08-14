<?php

namespace Cspray\Labrador\Http\Event;

use Amp\Future;
use Cspray\Labrador\AsyncEvent\AbstractListener;
use Cspray\Labrador\AsyncEvent\Event;
use Cspray\Labrador\Http\ApplicationEvent;
use Labrador\CompositeFuture\CompositeFuture;

abstract class ApplicationStartedListener extends AbstractListener {

    public function __construct() {
        parent::__construct(ApplicationEvent::ApplicationStarted->value);
    }

    final public function handle(Event $event) : Future|CompositeFuture|null {
        assert($event instanceof ApplicationStartedEvent);
        $this->do($event);
    }

    abstract protected function do(ApplicationStartedEvent $event) : Future|CompositeFuture|null;

}