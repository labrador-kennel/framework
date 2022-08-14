<?php

namespace Cspray\Labrador\Http\Test\Unit;

use Amp\Http\Server\Driver\Client;
use Amp\Http\Server\ErrorHandler;
use Amp\Http\Server\HttpServerStatus;
use Amp\Http\Server\Request;
use Amp\Http\Server\Response;
use Cspray\Labrador\Http\Application;
use Cspray\Labrador\Http\Event\AddRoutesEvent;
use Cspray\Labrador\Http\Event\ApplicationStartedEvent;
use Cspray\Labrador\Http\Event\ControllerInvokedEvent;
use Cspray\Labrador\Http\Event\ReceivingConnectionsEvent;
use Cspray\Labrador\Http\Event\RequestReceivedEvent;
use Cspray\Labrador\Http\Event\ResponseSentEvent;
use Cspray\Labrador\Http\Http\HttpMethod;
use Cspray\Labrador\Http\Http\RequestAttribute;
use Cspray\Labrador\Http\Router\Router;
use Cspray\Labrador\Http\Test\Unit\Stub\EventEmitterStub;
use Cspray\Labrador\Http\Test\Unit\Stub\HttpServerStub;
use Cspray\Labrador\Http\Test\Unit\Stub\ResponseControllerStub;
use Cspray\Labrador\Http\Test\Unit\Stub\RouterStub;
use League\Uri\Http;
use League\Uri\Uri;
use League\Uri\UriResolver;
use League\Uri\UriString;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;

final class ApplicationTest extends TestCase {

    private HttpServerStub $httpServer;
    private ErrorHandler&MockObject $errorHandler;
    private RouterStub $router;
    private EventEmitterStub $emitter;
    private Application $subject;

    protected function setUp() : void {
        parent::setUp();
        $this->httpServer = new HttpServerStub();
        $this->errorHandler = $this->getMockBuilder(ErrorHandler::class)->getMock();
        $this->router = new RouterStub();
        $this->emitter = new EventEmitterStub();
        $this->subject = new Application(
            $this->httpServer,
            $this->errorHandler,
            $this->router,
            $this->emitter,
            new NullLogger()
        );
    }

    public function testGetRouter() : void {
        self::assertSame($this->router, $this->subject->getRouter());
    }

    public function testCorrectEventsEmitted() : void {
        $this->subject->start();

        $events = $this->emitter->getEmittedEvents();

        self::assertCount(3, $events);
        self::assertInstanceOf(ApplicationStartedEvent::class, $events[0]);
        self::assertInstanceOf(AddRoutesEvent::class, $events[1]);
        self::assertInstanceOf(ReceivingConnectionsEvent::class, $events[2]);
    }

    public function testHttpServerStartedWhenReceivingConnectionsEventSent() : void {
        $this->subject->start();

        $events = $this->emitter->getEmittedEvents();

        self::assertCount(3, $events);
        self::assertInstanceOf(ReceivingConnectionsEvent::class, $events[2]);
        self::assertSame(HttpServerStatus::Started, $events[2]->getTarget()->getStatus());
    }

    public function testHttpServerReceivesRequestTriggersEvents() : void {
        $this->subject->start();

        $this->emitter->clearEmittedEvents();
        $this->router->setController($controller = new ResponseControllerStub($response = new Response()));

        $request = new Request(
            $this->getMockBuilder(Client::class)->getMock(),
            HttpMethod::Get->value,
            Http::createFromString('http://example.com'),
        );

        $actual = $this->httpServer->receiveRequest($request);
        $events = $this->emitter->getEmittedEvents();

        self::assertSame($response, $actual);
        self::assertCount(3, $events);
        self::assertInstanceOf(RequestReceivedEvent::class, $events[0]);
        self::assertInstanceOf(ControllerInvokedEvent::class, $events[1]);
        self::assertInstanceOf(ResponseSentEvent::class, $events[2]);
    }

    public function testRequestHasRequestId() : void {
        $this->subject->start();

        $this->emitter->clearEmittedEvents();
        $this->router->setController($controller = new ResponseControllerStub($response = new Response()));

        $request = new Request(
            $this->getMockBuilder(Client::class)->getMock(),
            HttpMethod::Get->value,
            Http::createFromString('http://example.com'),
        );

        $actual = $this->httpServer->receiveRequest($request);
        $events = $this->emitter->getEmittedEvents();

        self::assertSame($response, $actual);
        self::assertCount(3, $events);
        self::assertInstanceOf(UuidInterface::class, $id = $events[0]->getTarget()->getAttribute(RequestAttribute::RequestId->value));

        self::assertArrayHasKey(RequestAttribute::RequestId->value, $events[1]->getData());
        self::assertSame($id, $events[1]->getData()[RequestAttribute::RequestId->value]);

        self::assertArrayHasKey(RequestAttribute::RequestId->value, $events[2]->getData());
        self::assertSame($id, $events[2]->getData()[RequestAttribute::RequestId->value]);
    }


}