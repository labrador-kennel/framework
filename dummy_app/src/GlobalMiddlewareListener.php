<?php declare(strict_types=1);

namespace Labrador\DummyApp;

use Amp\Future;
use Labrador\AsyncEvent\Autowire\AutowiredListener;
use Labrador\AsyncEvent\Event;
use Labrador\AsyncEvent\Listener;
use Labrador\CompositeFuture\CompositeFuture;
use Labrador\DummyApp\Middleware\BarMiddleware;
use Labrador\DummyApp\Middleware\BazMiddleware;
use Labrador\DummyApp\Middleware\FooMiddleware;
use Labrador\DummyApp\Middleware\QuxMiddleware;
use Labrador\Web\Application\ApplicationEvent;
use Labrador\Web\Middleware\GlobalMiddlewareCollection;

#[AutowiredListener(ApplicationEvent::AddGlobalMiddleware->value)]
class GlobalMiddlewareListener implements Listener {

    public function __construct(
        private readonly MiddlewareCallRegistry $middlewareCallRegistry
    ) {}

    /**
     * @param Event<GlobalMiddlewareCollection> $event
     * @return Future|CompositeFuture|null
     */
    public function handle(Event $event) : Future|CompositeFuture|null {
        $event->payload()->add(new QuxMiddleware($this->middlewareCallRegistry));
        $event->payload()->add(new FooMiddleware($this->middlewareCallRegistry));
        $event->payload()->add(new BazMiddleware($this->middlewareCallRegistry));
        $event->payload()->add(new BarMiddleware($this->middlewareCallRegistry));

        return null;
    }
}