<?php

namespace Labrador\Test\Unit\Web\Application;

use Amp\Http\HttpStatus;
use Amp\Http\Server\DefaultErrorHandler;
use Amp\Http\Server\Driver\Client;
use Amp\Http\Server\ErrorHandler;
use Amp\Http\Server\HttpServerStatus;
use Amp\Http\Server\Request;
use Amp\Http\Server\Response;
use FastRoute\DataGenerator\GroupCountBased as GcbDataGenerator;
use FastRoute\Dispatcher\GroupCountBased as GcbDispatcher;
use FastRoute\RouteCollector;
use FastRoute\RouteParser\Std as StdRouteParser;
use Labrador\DummyApp\Middleware\BarMiddleware;
use Labrador\DummyApp\MiddlewareCallRegistry;
use Labrador\Test\Unit\Web\Stub\ErrorHandlerFactoryStub;
use Labrador\Test\Unit\Web\Stub\ErrorThrowingRequestHandler;
use Labrador\Test\Unit\Web\Stub\ErrorThrowingMiddleware;
use Labrador\Test\Unit\Web\Stub\ErrorThrowingRouter;
use Labrador\Test\Unit\Web\Stub\EventEmitterStub;
use Labrador\Test\Unit\Web\Stub\HttpServerStub;
use Labrador\Test\Unit\Web\Stub\KnownIncrementPreciseTime;
use Labrador\Test\Unit\Web\Stub\RequestAnalyticsQueueStub;
use Labrador\Test\Unit\Web\Stub\ResponseRequestHandlerStub;
use Labrador\Web\Application\AmpApplication;
use Labrador\Web\Application\Analytics\PreciseTime;
use Labrador\Web\Application\Analytics\RequestAnalytics;
use Labrador\Web\Application\Event\AddGlobalMiddleware;
use Labrador\Web\Application\Event\AddRoutes;
use Labrador\Web\Application\Event\ApplicationStarted;
use Labrador\Web\Application\Event\ApplicationStopped;
use Labrador\Web\Application\Event\ReceivingConnections;
use Labrador\Web\Application\Event\RequestReceived;
use Labrador\Web\Application\Event\ResponseSent;
use Labrador\Web\Application\Event\WillInvokeRequestHandler;
use Labrador\Web\HttpMethod;
use Labrador\Web\Middleware\GlobalMiddlewareCollection;
use Labrador\Web\RequestAttribute;
use Labrador\Web\Router\FastRouteRouter;
use Labrador\Web\Router\Mapping\GetMapping;
use Labrador\Web\Router\RoutingResolutionReason;
use League\Uri\Http;
use Monolog\Handler\TestHandler;
use Monolog\Logger;
use Monolog\Processor\PsrLogMessageProcessor;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;
use Ramsey\Uuid\UuidInterface;
use RuntimeException;

final class AmpApplicationTest extends TestCase {

    private HttpServerStub $httpServer;
    private ErrorHandler $errorHandler;
    private FastRouteRouter $router;
    private EventEmitterStub $emitter;
    private AmpApplication $subject;
    private TestHandler $testHandler;
    private RequestAnalyticsQueueStub $analyticsQueue;
    private PreciseTime $preciseTime;
    private GlobalMiddlewareCollection $globalMiddlewareCollection;

    protected function setUp() : void {
        parent::setUp();
        $this->httpServer = new HttpServerStub();
        $this->errorHandler = new DefaultErrorHandler();
        $this->router = new FastRouteRouter(
            new RouteCollector(new StdRouteParser(), new GcbDataGenerator()),
            function($data) { return new GcbDispatcher($data);
            }
        );
        $this->emitter = new EventEmitterStub();
        $this->testHandler = new TestHandler();
        $this->analyticsQueue = new RequestAnalyticsQueueStub();
        $this->preciseTime = new KnownIncrementPreciseTime(0, 1);
        $this->globalMiddlewareCollection = new GlobalMiddlewareCollection();
        $this->subject = new AmpApplication(
            $this->httpServer,
            (new ErrorHandlerFactoryStub($this->errorHandler))->createErrorHandler(),
            $this->router,
            $this->globalMiddlewareCollection,
            $this->emitter,
            new Logger('labrador-http-test', [$this->testHandler], [new PsrLogMessageProcessor()]),
            $this->analyticsQueue,
            $this->preciseTime
        );
    }

    public function testCorrectEventsEmitted() : void {
        $this->subject->start();

        $events = $this->emitter->getEmittedEvents();

        self::assertCount(4, $events);
        self::assertInstanceOf(ApplicationStarted::class, $events[0]);
        self::assertInstanceOf(AddGlobalMiddleware::class, $events[1]);
        self::assertInstanceOf(AddRoutes::class, $events[2]);
        self::assertInstanceOf(ReceivingConnections::class, $events[3]);
    }

    public function testHttpServerStartedWhenReceivingConnectionsEventSent() : void {
        $this->subject->start();

        $events = $this->emitter->getEmittedEvents();

        self::assertCount(4, $events);
        self::assertInstanceOf(ReceivingConnections::class, $events[3]);
        self::assertSame(HttpServerStatus::Started, $events[3]->payload()->getStatus());
    }

    public function testHttpServerReceivesRequestTriggersEvents() : void {
        $this->subject->start();

        $this->emitter->clearEmittedEvents();
        $this->router->addRoute(
            new GetMapping('/'),
            new ResponseRequestHandlerStub($response = new Response())
        );

        $request = new Request(
            $this->getMockBuilder(Client::class)->getMock(),
            HttpMethod::Get->value,
            Http::new('http://example.com'),
        );

        $actual = $this->httpServer->receiveRequest($request);
        $events = $this->emitter->getQueuedEvents();

        self::assertSame($response, $actual);
        self::assertCount(3, $events);
        self::assertInstanceOf(RequestReceived::class, $events[0]);
        self::assertInstanceOf(WillInvokeRequestHandler::class, $events[1]);
        self::assertInstanceOf(ResponseSent::class, $events[2]);
    }

    public function testRequestHasRequestId() : void {
        $this->subject->start();

        $this->emitter->clearEmittedEvents();
        $this->router->addRoute(
            new GetMapping('/'),
            new ResponseRequestHandlerStub($response = new Response())
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
        self::assertInstanceOf(UuidInterface::class, $events[0]->payload()->getAttribute(RequestAttribute::RequestId->value));
    }

    public function testApplicationStartedHasStartingUpLogs() : void {
        $this->subject->start();

        self::assertTrue($this->testHandler->hasInfoThatContains('Labrador HTTP application starting up.'));
    }

    public function testApplicationStartedHasAddingRoutesLogs() : void {
        $this->subject->start();

        self::assertTrue($this->testHandler->hasDebugThatContains('Allowing routes to be added through event system.'));
    }

    public function testApplicationStartedHasAddingGlobalMiddlewareLogs() : void {
        $this->subject->start();

        self::assertTrue($this->testHandler->hasDebugThatContains('Allowing global middleware to be added through event system.'));
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

    public function testMiddlewaresCalled() : void {
        $this->subject->start();

        $this->router->addRoute(
            new GetMapping('/'),
            $requestHandler = new ResponseRequestHandlerStub(new Response())
        );

        $request = new Request(
            $this->getMockBuilder(Client::class)->getMock(),
            HttpMethod::Get->value,
            Http::new('http://example.com'),
        );

        $this->globalMiddlewareCollection->add(
            new BarMiddleware(new MiddlewareCallRegistry())
        );

        $this->httpServer->receiveRequest($request);

        $expected = [
            'labrador.http.requestId' => $request->getAttribute(RequestAttribute::RequestId->value),
            'labrador.http.requestHandler' => $requestHandler,
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
        self::assertInstanceOf(ApplicationStopped::class, $this->emitter->getEmittedEvents()[0]);
    }

    public function testNormalProcessingHasCorrectRequestAnalyticsQueued() : void {
        $request = new Request(
            $this->getMockBuilder(Client::class)->getMock(),
            HttpMethod::Get->value,
            Http::new('https://example.com')
        );

        $this->router->addRoute(
            new GetMapping('/'),
            new ResponseRequestHandlerStub(new Response())
        );

        $this->subject->start();

        $response = $this->subject->handleRequest($request);

        self::assertSame(HttpStatus::OK, $response->getStatus());
        self::assertCount(1, $this->analyticsQueue->getQueuedRequestAnalytics());

        $analytics = $this->analyticsQueue->getQueuedRequestAnalytics()[0];

        self::assertInstanceOf(RequestAnalytics::class, $analytics);
        self::assertSame($request, $analytics->request());
        self::assertSame(RoutingResolutionReason::RequestMatched, $analytics->routingResolutionReason());
        self::assertSame(ResponseRequestHandlerStub::class, $analytics->requestHandlerName());
        self::assertSame(6, $analytics->totalTimeSpentInNanoSeconds());
        self::assertSame(1, $analytics->timeSpentRoutingInNanoSeconds());
        self::assertSame(1, $analytics->timeSpentProcessingMiddlewareInNanoseconds());
        self::assertSame(1, $analytics->timeSpentProcessingRequestHandlerInNanoseconds());
        self::assertSame(200, $analytics->responseStatusCode());
    }

    public function testExceptionThrownInRouterHasCorrectRequestAnalytics() : void {
        $request = new Request(
            $this->getMockBuilder(Client::class)->getMock(),
            HttpMethod::Get->value,
            Http::new('https://example.com')
        );

        $this->router->addRoute(
            new GetMapping('/'),
            new ResponseRequestHandlerStub(new Response())
        );

        $subject = new AmpApplication(
            $this->httpServer,
            (new ErrorHandlerFactoryStub($this->errorHandler))->createErrorHandler(),
            new ErrorThrowingRouter($exception = new RuntimeException()),
            new GlobalMiddlewareCollection(),
            $this->emitter,
            new NullLogger(),
            $this->analyticsQueue,
            $this->preciseTime
        );

        $subject->start();

        $response = $subject->handleRequest($request);

        self::assertSame(HttpStatus::INTERNAL_SERVER_ERROR, $response->getStatus());
        self::assertCount(1, $this->analyticsQueue->getQueuedRequestAnalytics());

        $analytics = $this->analyticsQueue->getQueuedRequestAnalytics()[0];

        self::assertInstanceOf(RequestAnalytics::class, $analytics);
        self::assertSame($request, $analytics->request());
        self::assertNull($analytics->requestHandlerName());
        self::assertNull($analytics->routingResolutionReason());
        self::assertSame($exception, $analytics->thrownException());
        self::assertSame(2, $analytics->totalTimeSpentInNanoSeconds());
        self::assertSame(1, $analytics->timeSpentRoutingInNanoSeconds());
        self::assertSame(0, $analytics->timeSpentProcessingMiddlewareInNanoseconds());
        self::assertSame(0, $analytics->timeSpentProcessingRequestHandlerInNanoseconds());
    }

    public function testExceptionThrownInMiddlewareHasCorrectRequestAnalytics() : void {
        $request = new Request(
            $this->getMockBuilder(Client::class)->getMock(),
            HttpMethod::Get->value,
            Http::new('https://example.com')
        );

        $exception = new RuntimeException();

        $this->router->addRoute(
            new GetMapping('/'),
            new ResponseRequestHandlerStub(new Response()),
        );

        $this->globalMiddlewareCollection->add(new ErrorThrowingMiddleware($exception));
        $this->subject->start();

        $response = $this->subject->handleRequest($request);

        self::assertSame(HttpStatus::INTERNAL_SERVER_ERROR, $response->getStatus());
        self::assertCount(1, $this->analyticsQueue->getQueuedRequestAnalytics());

        $analytics = $this->analyticsQueue->getQueuedRequestAnalytics()[0];

        self::assertInstanceOf(RequestAnalytics::class, $analytics);
        self::assertSame($request, $analytics->request());
        self::assertNull($analytics->requestHandlerName());
        self::assertSame(RoutingResolutionReason::RequestMatched, $analytics->routingResolutionReason());
        self::assertSame($exception, $analytics->thrownException());
        self::assertSame(4, $analytics->totalTimeSpentInNanoSeconds());
        self::assertSame(1, $analytics->timeSpentRoutingInNanoSeconds());
        self::assertSame(1, $analytics->timeSpentProcessingMiddlewareInNanoseconds());
        self::assertSame(0, $analytics->timeSpentProcessingRequestHandlerInNanoseconds());
    }

    public function testExceptionThrownInRequestHandlerHasCorrectRequestAnalytics() : void {
        $request = new Request(
            $this->getMockBuilder(Client::class)->getMock(),
            HttpMethod::Get->value,
            Http::new('https://example.com')
        );

        $exception = new RuntimeException();

        $this->router->addRoute(
            new GetMapping('/'),
            new ErrorThrowingRequestHandler($exception)
        );

        $this->subject->start();

        $response = $this->subject->handleRequest($request);

        self::assertSame(HttpStatus::INTERNAL_SERVER_ERROR, $response->getStatus());
        self::assertCount(1, $this->analyticsQueue->getQueuedRequestAnalytics());

        $analytics = $this->analyticsQueue->getQueuedRequestAnalytics()[0];

        self::assertInstanceOf(RequestAnalytics::class, $analytics);
        self::assertSame($request, $analytics->request());
        self::assertSame(ErrorThrowingRequestHandler::class, $analytics->requestHandlerName());
        self::assertSame(RoutingResolutionReason::RequestMatched, $analytics->routingResolutionReason());
        self::assertSame($exception, $analytics->thrownException());
        self::assertSame(6, $analytics->totalTimeSpentInNanoSeconds());
        self::assertSame(1, $analytics->timeSpentRoutingInNanoSeconds());
        self::assertSame(1, $analytics->timeSpentProcessingMiddlewareInNanoseconds());
        self::assertSame(1, $analytics->timeSpentProcessingRequestHandlerInNanoseconds());
    }

    public function testRouterResolutionNotFoundHasRequestHandlerProcessingTime() : void {
        $request = new Request(
            $this->getMockBuilder(Client::class)->getMock(),
            HttpMethod::Get->value,
            Http::createFromString('https://example.com')
        );

        $this->subject->start();

        $response = $this->subject->handleRequest($request);

        self::assertSame(HttpStatus::NOT_FOUND, $response->getStatus());
        self::assertCount(1, $this->analyticsQueue->getQueuedRequestAnalytics());

        $analytics = $this->analyticsQueue->getQueuedRequestAnalytics()[0];

        self::assertInstanceOf(RequestAnalytics::class, $analytics);
        self::assertSame($request, $analytics->request());
        self::assertNull($analytics->requestHandlerName());
        self::assertSame(RoutingResolutionReason::NotFound, $analytics->routingResolutionReason());
        self::assertNull($analytics->thrownException());
        self::assertSame(3, $analytics->totalTimeSpentInNanoSeconds());
        self::assertSame(1, $analytics->timeSpentRoutingInNanoSeconds());
        self::assertSame(0, $analytics->timeSpentProcessingMiddlewareInNanoseconds());
        self::assertSame(0, $analytics->timeSpentProcessingRequestHandlerInNanoseconds());
    }

    public function testRequestHandlerAddedToRequestAttribute() : void {
        $this->subject->start();

        $this->router->addRoute(
            new GetMapping('/'),
            $requestHandler = new ResponseRequestHandlerStub(new Response())
        );

        $request = new Request(
            $this->getMockBuilder(Client::class)->getMock(),
            HttpMethod::Get->value,
            Http::createFromString('http://example.com'),
        );

        $this->subject->handleRequest($request);

        self::assertSame(
            $requestHandler,
            $request->getAttribute(RequestAttribute::RequestHandler->value)
        );
    }
}
