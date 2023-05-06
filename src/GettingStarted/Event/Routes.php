<?php declare(strict_types=1);

namespace Labrador\Http\GettingStarted\Event;

use Amp\Future;
use Labrador\CompositeFuture\CompositeFuture;
use Labrador\Http\Event\AddRoutes;
use Labrador\Http\Event\AddRoutesListener;
use Labrador\Http\GettingStarted\Controller\Home;
use Labrador\Http\Router\GetMapping;

final class Routes extends AddRoutesListener {

    protected function handle(AddRoutes $addRoutes) : Future|CompositeFuture|null {
        $addRoutes->getTarget()->addRoute(
            new GetMapping('/'),
            new Home()
        );
        return null;
    }
}