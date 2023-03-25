<?php declare(strict_types=1);

namespace Labrador\Http\Test\Unit\Event;

use Amp\Future;
use Amp\Http\Server\Driver\Client;
use Amp\Http\Server\HttpServer;
use Amp\Http\Server\Request;
use Amp\Http\Server\Response;
use Amp\PHPUnit\AsyncTestCase;
use Labrador\AsyncEvent\AmpEventEmitter;
use Labrador\AsyncEvent\Event;
use Labrador\AsyncEvent\ListenerProvider;
use Labrador\CompositeFuture\CompositeFuture;
use Labrador\Http\Application;
use Labrador\Http\Controller\Controller;
use Labrador\Http\Event\AddRoutes;
use Labrador\Http\Event\AddRoutesListener;
use Labrador\Http\Event\ApplicationStarted;
use Labrador\Http\Event\ApplicationStartedListener;
use Labrador\Http\Event\ApplicationStopped;
use Labrador\Http\Event\ApplicationStoppedListener;
use Labrador\Http\Event\ReceivingConnections;
use Labrador\Http\Event\ReceivingConnectionsListener;
use Labrador\Http\Event\RequestReceived;
use Labrador\Http\Event\RequestReceivedListener;
use Labrador\Http\Event\ResponseSent;
use Labrador\Http\Event\ResponseSentListener;
use Labrador\Http\Event\WillInvokeController;
use Labrador\Http\Event\WillInvokeControllerListener;
use Labrador\Http\HttpMethod;
use Labrador\Http\Router\Router;
use League\Uri\Http;
use Ramsey\Uuid\Uuid;

final class ListenerProvidersTest extends AsyncTestCase {

    public function listenerProviderProvider() : array {
        return [
            AddRoutesListener::class => [
                new class extends AddRoutesListener {
                    protected function handle(AddRoutes $addRoutes) : Future|CompositeFuture|null {
                        return Future::complete($addRoutes::class);
                    }
                },
                new AddRoutes($this->getMockBuilder(Router::class)->getMock()),
                AddRoutes::class
            ],
            ApplicationStartedListener::class => [
                new class extends ApplicationStartedListener {
                    protected function handle(ApplicationStarted $applicationStarted) : Future|CompositeFuture|null {
                        return Future::complete($applicationStarted::class);
                    }
                },
                new ApplicationStarted($this->getMockBuilder(Application::class)->getMock()),
                ApplicationStarted::class
            ],
            ApplicationStoppedListener::class => [
                new class extends ApplicationStoppedListener {
                    protected function handle(ApplicationStopped $applicationStopped) : Future|CompositeFuture|null {
                        return Future::complete($applicationStopped::class);
                    }
                },
                new ApplicationStopped($this->getMockBuilder(Application::class)->getMock()),
                ApplicationStopped::class
            ],
            ReceivingConnectionsListener::class => [
                new class extends ReceivingConnectionsListener {
                    protected function handle(ReceivingConnections $receivingConnections) : Future|CompositeFuture|null {
                        return Future::complete($receivingConnections::class);
                    }
                },
                new ReceivingConnections($this->getMockBuilder(HttpServer::class)->getMock()),
                ReceivingConnections::class
            ],
            RequestReceivedListener::class => [
                new class extends RequestReceivedListener {
                    protected function handle(RequestReceived $requestReceived) : Future|CompositeFuture|null {
                        return Future::complete($requestReceived::class);
                    }
                },
                new RequestReceived(new Request(
                    $this->getMockBuilder(Client::class)->getMock(),
                    HttpMethod::Get->value,
                    Http::createFromString('https://example.com')
                )),
                RequestReceived::class
            ],
            ResponseSentListener::class => [
                new class extends ResponseSentListener {
                    protected function handle(ResponseSent $responseSent) : Future|CompositeFuture|null {
                        return Future::complete($responseSent::class);
                    }
                },
                new ResponseSent(new Response(), Uuid::uuid4()),
                ResponseSent::class
            ],
            WillInvokeControllerListener::class => [
                new class extends WillInvokeControllerListener {
                    protected function handle(WillInvokeController $willInvokeController) : Future|CompositeFuture|null {
                        return Future::complete($willInvokeController::class);
                    }
                },
                new WillInvokeController($this->getMockBuilder(Controller::class)->getMock(), Uuid::uuid4()),
                WillInvokeController::class
            ]
        ];
    }

    /**
     * @dataProvider listenerProviderProvider
     */
    public function testEmittingAddRoutesTriggersListener(
        ListenerProvider $subject,
        Event $event,
        string $expected
    ) : void {
        $emitter = new AmpEventEmitter();
        $emitter->register($subject);
        $actual = $emitter->emit($event)->await();

        self::assertSame(
            $actual,
            [$expected]
        );
    }

}
