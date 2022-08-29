<?php

namespace Cspray\Labrador\Http\Test\Unit;

use Amp\Http\Server\Driver\Client;
use Amp\Http\Server\ErrorHandler;
use Amp\Http\Server\HttpServerStatus;
use Amp\Http\Server\Request;
use Amp\Http\Server\Response;
use Amp\Http\Status;
use Cspray\Labrador\Http\AmpApplication;
use Cspray\Labrador\Http\DefaultErrorHandlerFactory;
use Cspray\Labrador\Http\Event\AddRoutesEvent;
use Cspray\Labrador\Http\Event\ApplicationStartedEvent;
use Cspray\Labrador\Http\Event\ApplicationStoppedEvent;
use Cspray\Labrador\Http\Event\ReceivingConnectionsEvent;
use Cspray\Labrador\Http\Event\RequestReceivedEvent;
use Cspray\Labrador\Http\Event\ResponseSentEvent;
use Cspray\Labrador\Http\Event\WillInvokeControllerEvent;
use Cspray\Labrador\Http\HttpMethod;
use Cspray\Labrador\Http\Middleware\Priority;
use Cspray\Labrador\Http\RequestAttribute;
use Cspray\Labrador\Http\Router\FastRouteRouter;
use Cspray\Labrador\Http\Router\RequestMapping;
use Cspray\Labrador\Http\Test\Unit\Stub\ErrorHandlerFactoryStub;
use Cspray\Labrador\Http\Test\Unit\Stub\EventEmitterStub;
use Cspray\Labrador\Http\Test\Unit\Stub\HttpServerStub;
use Cspray\Labrador\Http\Test\Unit\Stub\ResponseControllerStub;
use Cspray\Labrador\HttpDummyApp\Middleware\BarMiddleware;
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
        $this->router = new FastRouteRouter(
            new RouteCollector(new StdRouteParser(), new GcbDataGenerator()),
            function($data) { return new GcbDispatcher($data); }
        );
        $this->emitter = new EventEmitterStub();
        $this->testHandler = new TestHandler();
        $this->subject = new AmpApplication(
            $this->httpServer,
            new ErrorHandlerFactoryStub($this->errorHandler),
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

    public function testApplicationStoppedHasStoppingLogs() : void {
        $this->subject->start();
        $this->subject->stop();

        self::assertTrue($this->testHandler->hasInfoThatContains('Labrador HTTP application stopping.'));
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
        self::assertTrue($this->testHandler->hasRecord(
            [
                'message' => 'Finished processing Request id: ' . $id . '.',
                'requestId' => $id
            ],
            LogLevel::INFO
        ));
    }

    public function testRouteNotFoundCallsErrorHandlerAndIsLogged() : void {
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

        self::assertInstanceOf(UuidInterface::class, $id = $request->getAttribute(RequestAttribute::RequestId->value));

        self::assertSame($response, $actual);
        self::assertTrue($this->testHandler->hasRecord(
            [
                'message' => 'Did not find matching controller for Request id: ' . $id . '.',
                'requestId' => $id->toString()
            ],
            LogLevel::NOTICE
        ));
    }

    public function testRouteMethodNotAllowedCallsErrorHandlerAndIsLogged() : void {
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

        self::assertInstanceOf(UuidInterface::class, $id = $request->getAttribute(RequestAttribute::RequestId->value));

        self::assertSame($response, $actual);
        self::assertTrue($this->testHandler->hasRecord(
            [
                'message' => 'Method GET is not allowed on path / for Request id: ' . $id . '.',
                'method' => 'GET',
                'path' => '/',
                'requestId' => $id->toString()
            ],
            LogLevel::NOTICE
        ));

        self::assertCount(2, $this->emitter->getQueuedEvents());
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
            new BarMiddleware(new MiddlewareCallRegistry())
        );

        $this->httpServer->receiveRequest($request);

        $expected = [
            'labrador.http.requestId' => $request->getAttribute(RequestAttribute::RequestId->value),
            'labrador.http-dummy-app.middleware.bar' => 'low',
        ];

        self::assertSame($expected, $request->getAttributes());
    }

    public function testStartAndStopDelegatedToHttpServer() : void {
        self::assertSame(HttpServerStatus::Stopped, $this->httpServer->getStatus());

        $this->subject->start();

        self::assertSame(HttpServerStatus::Started, $this->httpServer->getStatus());

        $this->subject->stop();

        self::assertSame(HttpServerStatus::Stopped, $this->httpServer->getStatus());
    }

    public function testStopEventEmitted() : void {
        $this->subject->start();

        $this->emitter->clearEmittedEvents();

        $this->subject->stop();

        self::assertCount(1, $this->emitter->getEmittedEvents());
        self::assertInstanceOf(ApplicationStoppedEvent::class, $this->emitter->getEmittedEvents()[0]);
    }

}