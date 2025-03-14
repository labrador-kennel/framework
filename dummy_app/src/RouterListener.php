<?php declare(strict_types=1);

namespace Labrador\DummyApp;

use Amp\Future;
use Amp\Http\Server\Request;
use Amp\Http\Server\Response;
use Labrador\AsyncEvent\Autowire\AutowiredListener;
use Labrador\AsyncEvent\Event;
use Labrador\AsyncEvent\Listener;
use Labrador\CompositeFuture\CompositeFuture;
use Labrador\DummyApp\Middleware\BarMiddleware;
use Labrador\DummyApp\Middleware\BazMiddleware;
use Labrador\DummyApp\Middleware\FooMiddleware;
use Labrador\DummyApp\Middleware\QuxMiddleware;
use Labrador\Web\Application\ApplicationEvent;
use Labrador\Web\Application\Event\AddRoutes;
use Labrador\Web\Application\Event\RouterAndGlobalMiddlewareCollection;
use Labrador\Web\Controller\Controller;
use Labrador\Web\Router\Mapping\GetMapping;

#[AutowiredListener(ApplicationEvent::AddRoutes->value)]
class RouterListener implements Listener {

    public function __construct(
        private readonly MiddlewareCallRegistry $middlewareCallRegistry
    ) {}

    /**
     * @param Event<RouterAndGlobalMiddlewareCollection> $event
     * @return Future|CompositeFuture|null
     */
    public function handle(Event $event) : Future|CompositeFuture|null {
        $event->payload()->globalMiddleware()->add(new QuxMiddleware($this->middlewareCallRegistry));
        $event->payload()->globalMiddleware()->add(new FooMiddleware($this->middlewareCallRegistry));
        $event->payload()->globalMiddleware()->add(new BazMiddleware($this->middlewareCallRegistry));
        $event->payload()->globalMiddleware()->add(new BarMiddleware($this->middlewareCallRegistry));
        $event->payload()->router()->addRoute(
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