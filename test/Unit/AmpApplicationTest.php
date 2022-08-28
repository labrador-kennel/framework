<?php

namespace Cspray\Labrador\Http\Test\Unit;

use Amp\Http\Server\Driver\Client;
use Amp\Http\Server\ErrorHandler;
use Amp\Http\Server\HttpServerStatus;
use Amp\Http\Server\Request;
use Amp\Http\Server\Response;
use Amp\Http\Status;
use Cspray\Labrador\Http\AmpApplication;
use Cspray\Labrador\Http\ErrorHandlerFactory;
use Cspray\Labrador\Http\Event\AddRoutesEvent;
use Cspray\Labrador\Http\Event\ApplicationStartedEvent;
use Cspray\Labrador\Http\Event\ReceivingConnectionsEvent;
use Cspray\Labrador\Http\Event\RequestReceivedEvent;
use Cspray\Labrador\Http\Event\ResponseSentEvent;
use Cspray\Labrador\Http\Event\WillInvokeControllerEvent;
use Cspray\Labrador\Http\HttpMethod;
use Cspray\Labrador\Http\Middleware\Priority;
use Cspray\Labrador\Http\RequestAttribute;
use Cspray\Labrador\Http\Router\FastRouteRouter;
use Cspray\Labrador\Http\Router\RequestMapping;
use Cspray\Labrador\Http\Test\Unit\Stub\EventEmitterStub;
use Cspray\Labrador\Http\Test\Unit\Stub\HttpServerStub;
use Cspray\Labrador\Http\Test\Unit\Stub\ResponseControllerStub;
use Cspray\Labrador\HttpDummyApp\AppMiddleware\BarMiddleware;
use Cspray\Labrador\HttpDummyApp\MiddlewareCallRegistry;
use FastRoute\DataGenerator\GroupCountBased as GcbDataGenerator;
use FastRoute\Dispatcher\GroupCountBased as GcbDispatcher;
use FastRoute\RouteCollector;
use FastRoute\RouteParser\Std as StdRouteParser;
use League\Uri\Http;
use Monolog\Handler\TestHandler;
use Monolog\Logger;
use Monolog\Processor\PsrLogMessageProcessor;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LogLevel;
use Ramsey\Uuid\UuidInterface;

final class AmpApplicationTest extends TestCase {

    private HttpServerStub $httpServer;
    private ErrorHandler&MockObject $errorHandler;
    private FastRouteRouter $router;
    private EventEmitterStub $emitter;
    private AmpApplication $subject;
    private TestHandler $testHandler;

    protected function setUp() : void {
        parent::setUp();
        $this->httpServer = new HttpServerStub();
        $this->errorHandler = $this->getMockBuilder(ErrorHandler::class)->getMock();
        $errorHandlerFactory = new ErrorHandlerFactory();
        $errorHandlerFactory->setErrorHandler($this->errorHandler);
        $this->router = new FastRouteRouter(
            new RouteCollector(new StdRouteParser(), new GcbDataGenerator()),
            function($data) { return new GcbDispatcher($data); }
        );
        $this->emitter = new EventEmitterStub();
        $this->testHandler = new TestHandler();
        $this->subject = new AmpApplication(
            $this->httpServer,
            $errorHandlerFactory,
            $this->router,
            $this->emitter,
            new Logger('labrador-http-test', [$this->testHandler], [new PsrLogMessageProcessor()])
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
        $this->router->addRoute(
            RequestMapping::fromMethodAndPath(HttpMethod::Get, '/'),
            new ResponseControllerStub($response = new Response())
        );

        $request = new Request(
            $this->getMockBuilder(Client::class)->getMock(),
            HttpMethod::Get->value,
            Http::createFromString('http://example.com'),
        );

        $actual = $this->httpServer->receiveRequest($request);
        $events = $this->emitter->getQueuedEvents();

        self::assertSame($response, $actual);
        self::assertCount(3, $events);
        self::assertInstanceOf(RequestReceivedEvent::class, $events[0]);
        self::assertInstanceOf(WillInvokeControllerEvent::class, $events[1]);
        self::assertInstanceOf(ResponseSentEvent::class, $events[2]);
    }

    public function testRequestHasRequestId() : void {
        $this->subject->start();

        $this->emitter->clearEmittedEvents();
        $this->router->addRoute(
            RequestMapping::fromMethodAndPath(HttpMethod::Get, '/'),
            new ResponseControllerStub($response = new Response())
        );

        $request = new Request(
            $this->getMockBuilder(Client::class)->getMock(),
            HttpMethod::Get->value,
            Http::createFromString('http://example.com'),
        );

        $actual = $this->httpServer->receiveRequest($request);
        $events = $this->emitter->getQueuedEvents();

        self::assertSame($response, $actual);
        self::assertCount(3, $events);
        self::assertInstanceOf(UuidInterface::class, $id = $events[0]->getTarget()->getAttribute(RequestAttribute::RequestId->value));

        self::assertArrayHasKey(RequestAttribute::RequestId->value, $events[1]->getData());
        self::assertSame($id, $events[1]->getData()[RequestAttribute::RequestId->value]);

        self::assertArrayHasKey(RequestAttribute::RequestId->value, $events[2]->getData());
        self::assertSame($id, $events[2]->getData()[RequestAttribute::RequestId->value]);
    }

    public function testApplicationStartedHasStartingUpLogs() : void {
        $this->subject->start();

        self::assertTrue($this->testHandler->hasInfoThatContains('Labrador HTTP application starting up.'));
    }

    public function testApplicationStartedHasAddingRoutesLogs() : void {
        $this->subject->start();

        self::assertTrue($this->testHandler->hasInfoThatContains('Allowing routes to be added through event system.'));
    }

    public function testApplicationStartedHasReceivingConnectionsLogs() : void {
        $this->subject->start();

        self::assertTrue($this->testHandler->hasInfoThatContains('Application server is responding to requests.'));
    }

    public function testApplicationReceivesRequestLogsControllerMatched() : void {
        $this->subject->start();

        $this->emitter->clearEmittedEvents();
        $this->router->addRoute(
            RequestMapping::fromMethodAndPath(HttpMethod::Get, '/'),
            new ResponseControllerStub($response = new Response())
        );

        $request = new Request(
            $this->getMockBuilder(Client::class)->getMock(),
            HttpMethod::Get->value,
            Http::createFromString('http://example.com'),
        );

        $this->errorHandler->expects($this->never())->method('handleError');

        $this->httpServer->receiveRequest($request);

        self::assertInstanceOf(UuidInterface::class, $id = $request->getAttribute(RequestAttribute::RequestId->value));

        self::assertTrue($this->testHandler->hasRecord(
            [
                'message' => 'Started processing GET http://example.com - Request id: ' . $id . '.',
                'method' => 'GET',
                'url' => 'http://example.com',
                'requestId' => $id
            ],
            LogLevel::INFO
        ));
        self::assertTrue($this->testHandler->hasRecord(
            [
                'message' => 'Found matching controller, ' . ResponseControllerStub::class . ', for Request id: ' . $id . '.',
                'controller' => ResponseControllerStub::class,
                'requestId' => $id
            ],
            LogLevel::INFO
        ));
    }

    public function testRouteNotFoundCallsErrorHandler() : void {
        $this->subject->start();

        $this->emitter->clearEmittedEvents();

        $request = new Request(
            $this->getMockBuilder(Client::class)->getMock(),
            HttpMethod::Get->value,
            Http::createFromString('http://example.com'),
        );

        $this->errorHandler->expects($this->once())
            ->method('handleError')
            ->with(Status::NOT_FOUND, 'Not Found', $request)
            ->willReturn($response = new Response());

        $actual = $this->subject->handleRequest($request);

        self::assertSame($response, $actual);
    }

    public function testRouteMethodNotAllowedCallsErrorHandler() : void {
        $this->subject->start();

        $this->emitter->clearEmittedEvents();

        $this->router->addRoute(
            RequestMapping::fromMethodAndPath(HttpMethod::Post, '/'),
            new ResponseControllerStub(new Response(body: ''))
        );

        $request = new Request(
            $this->getMockBuilder(Client::class)->getMock(),
            HttpMethod::Get->value,
            Http::createFromString('http://example.com'),
        );

        $this->errorHandler->expects($this->once())
            ->method('handleError')
            ->with(Status::METHOD_NOT_ALLOWED, 'Method Not Allowed', $request)
            ->willReturn($response = new Response());

        $actual = $this->subject->handleRequest($request);

        self::assertSame($response, $actual);
    }

    public function testMiddlewaresCalled() : void {
        $this->subject->start();

        $this->emitter->clearEmittedEvents();
        $this->router->addRoute(
            RequestMapping::fromMethodAndPath(HttpMethod::Get, '/'),
            new ResponseControllerStub(new Response())
        );

        $request = new Request(
            $this->getMockBuilder(Client::class)->getMock(),
            HttpMethod::Get->value,
            Http::createFromString('http://example.com'),
        );

        $this->subject->addMiddleware(
            new BarMiddleware(new MiddlewareCallRegistry()),
            Priority::Low
        );

        $this->httpServer->receiveRequest($request);

        $expected = [
            'labrador.http.requestId' => $request->getAttribute(RequestAttribute::RequestId->value),
            'labrador.http-dummy-app.middleware.bar' => 'low',
        ];

        self::assertSame($expected, $request->getAttributes());
    }

}