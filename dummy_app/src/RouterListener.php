<?php declare(strict_types=1);

namespace Labrador\DummyApp;

use Amp\Future;
use Amp\Http\Server\Request;
use Amp\Http\Server\RequestHandler;
use Amp\Http\Server\Response;
use Cspray\AnnotatedContainer\Autowire\AutowireableFactory;
use Labrador\AsyncEvent\Autowire\AutowiredListener;
use Labrador\AsyncEvent\Event;
use Labrador\AsyncEvent\Listener;
use Labrador\CompositeFuture\CompositeFuture;
use Labrador\DummyApp\RequestHandler\HelloMiddlewareRequestHandler;
use Labrador\DummyApp\RequestHandler\HelloWorldRequestHandler;
use Labrador\DummyApp\Middleware\RequestHandlerSpecificMiddleware;
use Labrador\Web\Application\ApplicationEvent;
use Labrador\Web\Router\FriendlyRouter;
use Labrador\Web\Router\Router;

#[AutowiredListener(ApplicationEvent::AddRoutes->value)]
class RouterListener implements Listener {

    public function __construct(
        private readonly AutowireableFactory $factory
    ) {}

    /**
     * @param Event<Router> $event
     * @return Future|CompositeFuture|null
     */
    public function handle(Event $event) : Future|CompositeFuture|null {
        $friendlyRouter = new FriendlyRouter($event->payload());

        $friendlyRouter->get(
            '/hello/world',
            $this->factory->make(HelloWorldRequestHandler::class)
        );
        $friendlyRouter->get(
            '/hello/middleware',
            $this->factory->make(HelloMiddlewareRequestHandler::class),
            $this->factory->make(RequestHandlerSpecificMiddleware::class)
        );

        $friendlyRouter->get(
            '/exception',
            new class implements RequestHandler {
                public function handleRequest(Request $request) : Response {
                    throw new \RuntimeException('A message detailing what went wrong that should show up in logs.');
                }
            }
        );

        return null;
    }
}