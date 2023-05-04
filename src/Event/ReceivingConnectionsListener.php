<?php declare(strict_types=1);

namespace Labrador\Http\Event;

use Amp\Future;
use Labrador\AsyncEvent\AbstractListenerProvider;
use Labrador\CompositeFuture\CompositeFuture;
use Labrador\Http\Application\ApplicationEvent;

abstract class ReceivingConnectionsListener extends AbstractListenerProvider {

    public function __construct() {
        parent::__construct(
            [ApplicationEvent::ReceivingConnections->value],
            $this->handle(...)
        );
    }

    abstract protected function handle(ReceivingConnections $receivingConnections) : Future|CompositeFuture|null;

}
