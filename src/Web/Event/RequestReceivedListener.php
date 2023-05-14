<?php declare(strict_types=1);

namespace Labrador\Web\Event;

use Amp\Future;
use Labrador\AsyncEvent\AbstractListenerProvider;
use Labrador\CompositeFuture\CompositeFuture;
use Labrador\Web\Application\ApplicationEvent;

abstract class RequestReceivedListener extends AbstractListenerProvider {

    public function __construct() {
        parent::__construct(
            [ApplicationEvent::RequestReceived->value],
            $this->handle(...)
        );
    }

    abstract protected function handle(RequestReceived $requestReceived) : Future|CompositeFuture|null;

}