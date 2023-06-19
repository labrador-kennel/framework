<?php

namespace Labrador\Test\Unit\Web\Application;

use Amp\Http\HttpStatus;
use Amp\Http\Server\DefaultErrorHandler;
use Amp\Http\Server\Driver\Client;
use Amp\Http\Server\ErrorHandler;
use Amp\Http\Server\HttpServerStatus;
use Amp\Http\Server\Middleware;
use Amp\Http\Server\Request;
use Amp\Http\Server\RequestHandler;
use Amp\Http\Server\Response;
use Amp\Http\Server\Session\LocalSessionStorage;
use Amp\Http\Server\Session\Session;
use Amp\Http\Server\Session\SessionFactory;
use Amp\Http\Server\Session\SessionMiddleware;
use Amp\Http\Server\Session\SessionStorage;
use Amp\Sync\LocalKeyedMutex;
use FastRoute\DataGenerator\GroupCountBased as GcbDataGenerator;
use FastRoute\Dispatcher\GroupCountBased as GcbDispatcher;
use FastRoute\RouteCollector;
use FastRoute\RouteParser\Std as StdRouteParser;
use Labrador\DummyApp\Middleware\BarMiddleware;
use Labrador\DummyApp\MiddlewareCallRegistry;
use Labrador\Test\Unit\Web\Stub\ErrorHandlerFactoryStub;
use Labrador\Test\Unit\Web\Stub\ErrorThrowingController;
use Labrador\Test\Unit\Web\Stub\ErrorThrowingMiddleware;
use Labrador\Test\Unit\Web\Stub\ErrorThrowingRouter;
use Labrador\Test\Unit\Web\Stub\EventEmitterStub;
use Labrador\Test\Unit\Web\Stub\HttpServerStub;
use Labrador\Test\Unit\Web\Stub\KnownIncrementPreciseTime;
use Labrador\Test\Unit\Web\Stub\RequestAnalyticsQueueStub;
use Labrador\Test\Unit\Web\Stub\RequireAccessReadSessionController;
use Labrador\Test\Unit\Web\Stub\ResponseControllerStub;
use Labrador\Test\Unit\Web\Stub\SessionGatheringController;
use Labrador\Test\Unit\Web\Stub\ToStringControllerStub;
use Labrador\Web\Application\AmpApplication;
use Labrador\Web\Application\Analytics\PreciseTime;
use Labrador\Web\Application\Analytics\RequestAnalytics;
use Labrador\Web\Application\ApplicationFeatures;
use Labrador\Web\Application\NoApplicationFeatures;
use Labrador\Web\Application\StaticAssetSettings;
use Labrador\Web\Event\AddRoutes;
use Labrador\Web\Event\ApplicationStarted;
use Labrador\Web\Event\ApplicationStopped;
use Labrador\Web\Event\ReceivingConnections;
use Labrador\Web\Event\RequestReceived;
use Labrador\Web\Event\ResponseSent;
use Labrador\Web\Event\WillInvokeController;
use Labrador\Web\HttpMethod;
use Labrador\Web\Middleware\Priority;
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

    private string $assetsDir;

    protected function setUp() : void {
        parent::setUp();
        $this->httpServer = new HttpServerStub();
        $this->errorHandler = new DefaultErrorHandler();
        $this->router = new FastRouteRouter(
            new RouteCollector(new StdRouteParser(), new GcbDataGenerator()),
            function($data) { return new GcbDispatcher($data); }
        );
        $this->emitter = new EventEmitterStub();
        $this->testHandler = new TestHandler();
        $this->analyticsQueue = new RequestAnalyticsQueueStub();
        $this->preciseTime = new KnownIncrementPreciseTime(0, 1);
        $this->assetsDir = dirname(__DIR__, 3) . '/Helper/assets';
        $this->subject = new AmpApplication(
            $this->httpServer,
            new ErrorHandlerFactoryStub($this->errorHandler),
            $this->router,
            $this->emitter,
            new Logger('labrador-http-test', [$this->testHandler], [new PsrLogMessageProcessor()]),
            new NoApplicationFeatures(),
            $this->analyticsQueue,
            $this->preciseTime
        );
    }

    public function testGetRouter() : void {
        self::assertSame($this->router, $this->subject->getRouter());
    }

    public function testCorrectEventsEmitted() : void {
        $this->subject->start();

        $events = $this->emitter->getEmittedEvents();

        self::assertCount(3, $events);
        self::assertInstanceOf(ApplicationStarted::class, $events[0]);
        self::assertInstanceOf(AddRoutes::class, $events[1]);
        self::assertInstanceOf(ReceivingConnections::class, $events[2]);
    }

    public function testHttpServerStartedWhenReceivingConnectionsEventSent() : void {
        $this->subject->start();

        $events = $this->emitter->getEmittedEvents();

        self::assertCount(3, $events);
        self::assertInstanceOf(ReceivingConnections::class, $events[2]);
        self::assertSame(HttpServerStatus::Started, $events[2]->getTarget()->getStatus());
    }

    public function testHttpServerReceivesRequestTriggersEvents() : void {
        $this->subject->start();

        $this->emitter->clearEmittedEvents();
        $this->router->addRoute(
            new GetMapping('/'),
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
        self::assertInstanceOf(RequestReceived::class, $events[0]);
        self::assertInstanceOf(WillInvokeController::class, $events[1]);
        self::assertInstanceOf(ResponseSent::class, $events[2]);
    }

    public function testRequestHasRequestId() : void {
        $this->subject->start();

        $this->emitter->clearEmittedEvents();
        $this->router->addRoute(
            new GetMapping('/'),
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

        self::assertTrue($this->testHandler->hasDebugThatContains('Allowing routes to be added through event system.'));
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
            $controller = new ResponseControllerStub(new Response())
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
            'labrador.http.controller' => $controller,
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

    private function getApplicationFeaturesWithSessionMiddleware(?SessionStorage $storage = null) : ApplicationFeatures {
        return new class($storage) implements ApplicationFeatures {

            public function __construct(
                private readonly ?SessionStorage $storage
            ) {}

            public function getSessionMiddleware() : ?SessionMiddleware {
                return new SessionMiddleware(
                    new SessionFactory(
                        new LocalKeyedMutex(),
                        $this->storage ?? new LocalSessionStorage(),
                    )
                );
            }

            public function autoRedirectHttpToHttps() : bool {
                return false;
            }

            public function getStaticAssetSettings() : ?StaticAssetSettings {
                return null;
            }

            public function getHttpsRedirectPort() : ?int {
                return null;
            }
        };
    }

    private function getApplicationFeaturesWithHttpToHttpsRedirectAndNoHttpsPort() : ApplicationFeatures {
        return new class implements ApplicationFeatures {

            public function getSessionMiddleware() : ?SessionMiddleware {
                return null;
            }

            public function autoRedirectHttpToHttps() : bool {
                return true;
            }

            public function getStaticAssetSettings() : ?StaticAssetSettings {
                return null;
            }

            public function getHttpsRedirectPort() : ?int {
                return null;
            }
        };
    }

    private function getApplicationFeaturesWithHttpToHttpsRedirectAndExplicitHttpsPort() : ApplicationFeatures {
        return new class implements ApplicationFeatures {

            public function getSessionMiddleware() : ?SessionMiddleware {
                return null;
            }

            public function autoRedirectHttpToHttps() : bool {
                return true;
            }

            public function getStaticAssetSettings() : ?StaticAssetSettings {
                return null;
            }

            public function getHttpsRedirectPort() : ?int {
                return 9001;
            }
        };
    }

    private function getApplicationFeaturesWithStaticAssetSettings() : ApplicationFeatures {
        return new class implements ApplicationFeatures {

            public function getSessionMiddleware() : ?SessionMiddleware {
                return null;
            }

            public function autoRedirectHttpToHttps() : bool {
                return false;
            }

            public function getStaticAssetSettings() : ?StaticAssetSettings {
                return new StaticAssetSettings(dirname(__DIR__, 3) . '/Helper/assets');
            }

            public function getHttpsRedirectPort() : ?int {
                return null;
            }
        };
    }

    public function testSessionFactoryPresentInAppFeaturesSetsSessionOnRequest() : void {
        $controller = new SessionGatheringController();
        $this->router->addRoute(
            new GetMapping('/session-test'),
            $controller
        );
        $request = new Request(
            $this->getMockBuilder(Client::class)->getMock(),
            HttpMethod::Get->value,
            Http::createFromString('http://example.com/session-test')
        );


        $subject = new AmpApplication(
            $this->httpServer,
            new ErrorHandlerFactoryStub($this->errorHandler),
            $this->router,
            $this->emitter,
            new Logger('labrador-http-test', [$this->testHandler], [new PsrLogMessageProcessor()]),
            $this->getApplicationFeaturesWithSessionMiddleware(),
            $this->analyticsQueue,
            $this->preciseTime
        );

        $subject->start();

        $response = $subject->handleRequest($request);

        self::assertSame('OK', $response->getBody()->read());
        self::assertNotNull($controller->getSession());
    }

    public function testSessionMiddlewareSetToCriticalLevelAndRunFirst() : void {
        $controller = new SessionGatheringController();
        $this->router->addRoute(
            new GetMapping('/session-test'),
            $controller
        );
        $request = new Request(
            $this->getMockBuilder(Client::class)->getMock(),
            HttpMethod::Get->value,
            Http::createFromString('http://example.com/session-test')
        );

        $subject = new AmpApplication(
            $this->httpServer,
            new ErrorHandlerFactoryStub($this->errorHandler),
            $this->router,
            $this->emitter,
            new Logger('labrador-http-test', [$this->testHandler], [new PsrLogMessageProcessor()]),
            $this->getApplicationFeaturesWithSessionMiddleware(),
            $this->analyticsQueue,
            $this->preciseTime
        );

        $middleware = new class implements Middleware {
            public ?Session $session = null;

            public function handleRequest(Request $request, RequestHandler $requestHandler) : Response {
                if ($request->hasAttribute(Session::class)) {
                    $this->session = $request->getAttribute(Session::class);
                }

                return $requestHandler->handleRequest($request);
            }
        };
        $subject->addMiddleware($middleware, Priority::Critical);

        $subject->start();

        $response = $subject->handleRequest($request);

        self::assertSame('OK', $response->getBody()->read());
        self::assertNotNull($middleware->session);
        self::assertNotNull($controller->getSession());
    }

    public function testApplicationFeaturesRedirectHttpToHttpsWithNullPort() : void {
        $request = new Request(
            $this->getMockBuilder(Client::class)->getMock(),
            HttpMethod::Get->value,
            Http::createFromString('http://example.com/tls-test?foo=bar')
        );

        $subject = new AmpApplication(
            $this->httpServer,
            new ErrorHandlerFactoryStub($this->errorHandler),
            $this->router,
            $this->emitter,
            new Logger('labrador-http-test', [$this->testHandler], [new PsrLogMessageProcessor()]),
            $this->getApplicationFeaturesWithHttpToHttpsRedirectAndNoHttpsPort(),
            $this->analyticsQueue,
            $this->preciseTime,
        );

        $subject->start();

        $response = $subject->handleRequest($request);

        self::assertSame(
            HttpStatus::MOVED_PERMANENTLY,
            $response->getStatus()
        );
        self::assertSame(
            ['location' => ['https://example.com/tls-test?foo=bar']],
            $response->getHeaders()
        );
    }

    public function testApplicationFeaturesRedirectHttpToHttpsWithExplicitPort() : void {
        $request = new Request(
            $this->getMockBuilder(Client::class)->getMock(),
            HttpMethod::Get->value,
            Http::createFromString('http://example.com/tls-test?foo=bar')
        );

        $subject = new AmpApplication(
            $this->httpServer,
            new ErrorHandlerFactoryStub($this->errorHandler),
            $this->router,
            $this->emitter,
            new Logger('labrador-http-test', [$this->testHandler], [new PsrLogMessageProcessor()]),
            $this->getApplicationFeaturesWithHttpToHttpsRedirectAndExplicitHttpsPort(),
            $this->analyticsQueue,
            $this->preciseTime,
        );

        $subject->start();

        $response = $subject->handleRequest($request);

        self::assertSame(
            HttpStatus::MOVED_PERMANENTLY,
            $response->getStatus()
        );
        self::assertSame(
            ['location' => ['https://example.com:9001/tls-test?foo=bar']],
            $response->getHeaders()
        );
    }

    public function testNormalProcessingHasCorrectRequestAnalyticsQueued() : void {
        $request = new Request(
            $this->getMockBuilder(Client::class)->getMock(),
            HttpMethod::Get->value,
            Http::createFromString('https://example.com')
        );

        $this->router->addRoute(
            new GetMapping('/'),
            new ToStringControllerStub('KnownController')
        );

        $this->subject->start();

        $response = $this->subject->handleRequest($request);

        self::assertSame(HttpStatus::OK, $response->getStatus());
        self::assertCount(1, $this->analyticsQueue->getQueuedRequestAnalytics());

        $analytics = $this->analyticsQueue->getQueuedRequestAnalytics()[0];

        self::assertInstanceOf(RequestAnalytics::class, $analytics);
        self::assertSame($request, $analytics->getRequest());
        self::assertSame(RoutingResolutionReason::RequestMatched, $analytics->getRoutingResolutionReason());
        self::assertSame('KnownController', $analytics->getControllerName());
        self::assertSame(6, $analytics->getTotalTimeSpentInNanoSeconds());
        self::assertSame(1, $analytics->getTimeSpentRoutingInNanoSeconds());
        self::assertSame(1, $analytics->getTimeSpentProcessingMiddlewareInNanoseconds());
        self::assertSame(1, $analytics->getTimeSpentProcessingControllerInNanoseconds());
        self::assertSame(200, $analytics->getResponseStatusCode());
    }

    public function testExceptionThrownInRouterHasCorrectRequestAnalytics() : void {
        $request = new Request(
            $this->getMockBuilder(Client::class)->getMock(),
            HttpMethod::Get->value,
            Http::createFromString('https://example.com')
        );

        $this->router->addRoute(
            new GetMapping('/'),
            new ToStringControllerStub('KnownController')
        );

        $subject = new AmpApplication(
            $this->httpServer,
            new ErrorHandlerFactoryStub($this->errorHandler),
            new ErrorThrowingRouter($exception = new RuntimeException()),
            $this->emitter,
            new NullLogger(),
            new NoApplicationFeatures(),
            $this->analyticsQueue,
            $this->preciseTime
        );

        $subject->start();

        $response = $subject->handleRequest($request);

        self::assertSame(HttpStatus::INTERNAL_SERVER_ERROR, $response->getStatus());
        self::assertCount(1, $this->analyticsQueue->getQueuedRequestAnalytics());

        $analytics = $this->analyticsQueue->getQueuedRequestAnalytics()[0];

        self::assertInstanceOf(RequestAnalytics::class, $analytics);
        self::assertSame($request, $analytics->getRequest());
        self::assertNull($analytics->getControllerName());
        self::assertNull($analytics->getRoutingResolutionReason());
        self::assertSame($exception, $analytics->getThrownException());
        self::assertSame(2, $analytics->getTotalTimeSpentInNanoSeconds());
        self::assertSame(1, $analytics->getTimeSpentRoutingInNanoSeconds());
        self::assertSame(0, $analytics->getTimeSpentProcessingMiddlewareInNanoseconds());
        self::assertSame(0, $analytics->getTimeSpentProcessingControllerInNanoseconds());
    }

    public function testExceptionThrownInMiddlewareHasCorrectRequestAnalytics() : void {
        $request = new Request(
            $this->getMockBuilder(Client::class)->getMock(),
            HttpMethod::Get->value,
            Http::createFromString('https://example.com')
        );

        $exception = new RuntimeException();

        $this->router->addRoute(
            new GetMapping('/'),
            new ToStringControllerStub('KnownController'),
        );

        $this->subject->addMiddleware(new ErrorThrowingMiddleware($exception));
        $this->subject->start();

        $response = $this->subject->handleRequest($request);

        self::assertSame(HttpStatus::INTERNAL_SERVER_ERROR, $response->getStatus());
        self::assertCount(1, $this->analyticsQueue->getQueuedRequestAnalytics());

        $analytics = $this->analyticsQueue->getQueuedRequestAnalytics()[0];

        self::assertInstanceOf(RequestAnalytics::class, $analytics);
        self::assertSame($request, $analytics->getRequest());
        self::assertNull($analytics->getControllerName());
        self::assertSame(RoutingResolutionReason::RequestMatched, $analytics->getRoutingResolutionReason());
        self::assertSame($exception, $analytics->getThrownException());
        self::assertSame(4, $analytics->getTotalTimeSpentInNanoSeconds());
        self::assertSame(1, $analytics->getTimeSpentRoutingInNanoSeconds());
        self::assertSame(1, $analytics->getTimeSpentProcessingMiddlewareInNanoseconds());
        self::assertSame(0, $analytics->getTimeSpentProcessingControllerInNanoseconds());
    }

    public function testExceptionThrownInControllerHasCorrectRequestAnalytics() : void {
        $request = new Request(
            $this->getMockBuilder(Client::class)->getMock(),
            HttpMethod::Get->value,
            Http::createFromString('https://example.com')
        );

        $exception = new RuntimeException();

        $this->router->addRoute(
            new GetMapping('/'),
            new ErrorThrowingController($exception)
        );

        $this->subject->start();

        $response = $this->subject->handleRequest($request);

        self::assertSame(HttpStatus::INTERNAL_SERVER_ERROR, $response->getStatus());
        self::assertCount(1, $this->analyticsQueue->getQueuedRequestAnalytics());

        $analytics = $this->analyticsQueue->getQueuedRequestAnalytics()[0];

        self::assertInstanceOf(RequestAnalytics::class, $analytics);
        self::assertSame($request, $analytics->getRequest());
        self::assertSame(ErrorThrowingController::class, $analytics->getControllerName());
        self::assertSame(RoutingResolutionReason::RequestMatched, $analytics->getRoutingResolutionReason());
        self::assertSame($exception, $analytics->getThrownException());
        self::assertSame(6, $analytics->getTotalTimeSpentInNanoSeconds());
        self::assertSame(1, $analytics->getTimeSpentRoutingInNanoSeconds());
        self::assertSame(1, $analytics->getTimeSpentProcessingMiddlewareInNanoseconds());
        self::assertSame(1, $analytics->getTimeSpentProcessingControllerInNanoseconds());
    }

    public function testRouterResolutionNotFoundHasControllerProcessingTime() : void {
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
        self::assertSame($request, $analytics->getRequest());
        self::assertNull($analytics->getControllerName());
        self::assertSame(RoutingResolutionReason::NotFound, $analytics->getRoutingResolutionReason());
        self::assertNull($analytics->getThrownException());
        self::assertSame(3, $analytics->getTotalTimeSpentInNanoSeconds());
        self::assertSame(1, $analytics->getTimeSpentRoutingInNanoSeconds());
        self::assertSame(0, $analytics->getTimeSpentProcessingMiddlewareInNanoseconds());
        self::assertSame(0, $analytics->getTimeSpentProcessingControllerInNanoseconds());
    }

    public function testControllerAddedToRequestAttribute() : void {
        $this->subject->start();

        $this->router->addRoute(
            new GetMapping('/'),
            $controller = new ResponseControllerStub(new Response())
        );

        $request = new Request(
            $this->getMockBuilder(Client::class)->getMock(),
            HttpMethod::Get->value,
            Http::createFromString('http://example.com'),
        );

        $this->subject->handleRequest($request);

        self::assertSame(
            $controller,
            $request->getAttribute(RequestAttribute::Controller->value)
        );
    }

    public function testStaticAssetSettingsProvidedHasControllerAddedAndReturnsCorrectFile() : void {
        $subject = new AmpApplication(
            $this->httpServer,
            new ErrorHandlerFactoryStub($this->errorHandler),
            $this->router,
            $this->emitter,
            new NullLogger(),
            $this->getApplicationFeaturesWithStaticAssetSettings(),
            $this->analyticsQueue,
            $this->preciseTime
        );

        $subject->start();

        $request = new Request(
            $this->getMockBuilder(Client::class)->getMock(),
            HttpMethod::Get->value,
            Http::createFromString('https://example.com/assets/main.css')
        );

        $response = $subject->handleRequest($request);

        self::assertSame(HttpStatus::OK, $response->getStatus());
        self::assertSame('text/css; charset=utf-8', $response->getHeader('Content-Type'));
        self::assertSame('html {}', $response->getBody()->read());
    }
}
