<?php declare(strict_types=1);

namespace Labrador\Test\Helper;

use Amp\Future;
use Labrador\AsyncEvent\Event;
use Labrador\AsyncEvent\Listener;
use Labrador\CompositeFuture\CompositeFuture;
use Labrador\Web\Router\Router;

final class StubAddRoutesListener implements Listener {

    /**
     * @param Event<Router> $event
     * @return Future|CompositeFuture|null
     */
    public function handle(Event $event) : Future|CompositeFuture|null {
    }
}