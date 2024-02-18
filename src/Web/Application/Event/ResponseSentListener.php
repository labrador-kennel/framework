<?php declare(strict_types=1);

namespace Labrador\Web\Application\Event;

use Amp\Future;
use Labrador\AsyncEvent\AbstractListenerProvider;
use Labrador\CompositeFuture\CompositeFuture;
use Labrador\Web\Application\ApplicationEvent;

abstract class ResponseSentListener extends AbstractListenerProvider {

    public function __construct() {
        parent::__construct(
            [ApplicationEvent::ResponseSent->value],
            $this->handle(...)
        );
    }

    abstract protected function handle(ResponseSent $responseSent) : Future|CompositeFuture|null;

}