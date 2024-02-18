<?php declare(strict_types=1);

namespace Labrador\Test\Unit\Web\Event;

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
use Labrador\Web\Application\Application;
use Labrador\Web\Application\Event\AddRoutes;
use Labrador\Web\Application\Event\AddRoutesListener;
use Labrador\Web\Application\Event\ApplicationStarted;
use Labrador\Web\Application\Event\ApplicationStartedListener;
use Labrador\Web\Application\Event\ApplicationStopped;
use Labrador\Web\Application\Event\ApplicationStoppedListener;
use Labrador\Web\Application\Event\ReceivingConnections;
use Labrador\Web\Application\Event\ReceivingConnectionsListener;
use Labrador\Web\Application\Event\RequestReceived;
use Labrador\Web\Application\Event\RequestReceivedListener;
use Labrador\Web\Application\Event\ResponseSent;
use Labrador\Web\Application\Event\ResponseSentListener;
use Labrador\Web\Application\Event\WillInvokeController;
use Labrador\Web\Application\Event\WillInvokeControllerListener;
use Labrador\Web\Controller\Controller;
use Labrador\Web\HttpMethod;
use Labrador\Web\Router\Router;
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
