<?php declare(strict_types=1);

namespace Labrador\HttpDummyApp;

use Amp\Future;
use Amp\Http\Server\Request;
use Amp\Http\Server\Response;
use Labrador\AsyncEvent\Autowire\ListenerService;
use Labrador\CompositeFuture\CompositeFuture;
use Labrador\Http\Controller\Controller;
use Labrador\Http\Event\AddRoutes;
use Labrador\Http\Event\AddRoutesListener;
use Labrador\Http\Router\GetMapping;

#[ListenerService]
class RouterListener extends AddRoutesListener {

    protected function handle(AddRoutes $addRoutes) : Future|CompositeFuture|null {
        $addRoutes->getTarget()->addRoute(
            new GetMapping('/exception'),
            new class implements Controller {

                public function toString() : string {
                    return 'ErrorThrowingController';
                }

                public function handleRequest(Request $request) : Response {
                    throw new \RuntimeException('A message detailing what went wrong that should show up in logs.');
                }
            }
        );

        return null;
    }
}