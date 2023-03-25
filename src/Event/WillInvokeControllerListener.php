<?php declare(strict_types=1);

namespace Labrador\Http\Event;

use Amp\Future;
use Labrador\AsyncEvent\AbstractListenerProvider;
use Labrador\CompositeFuture\CompositeFuture;
use Labrador\Http\ApplicationEvent;

abstract class WillInvokeControllerListener extends AbstractListenerProvider {

    public function __construct() {
        parent::__construct(
            [ApplicationEvent::WillInvokeController->value],
            $this->handle(...)
        );
    }

    abstract protected function handle(WillInvokeController $willInvokeController) : Future|CompositeFuture|null;

}