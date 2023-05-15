<?php declare(strict_types=1);

namespace Labrador\GettingStarted\Event;

use Amp\Future;
use Labrador\CompositeFuture\CompositeFuture;
use Labrador\GettingStarted\Controller\Home;
use Labrador\Web\Event\AddRoutes;
use Labrador\Web\Event\AddRoutesListener;
use Labrador\Web\Router\GetMapping;

final class Routes extends AddRoutesListener {

    protected function handle(AddRoutes $addRoutes) : Future|CompositeFuture|null {
        $addRoutes->getTarget()->addRoute(new GetMapping('/'), new Home());
        return null;
    }

}