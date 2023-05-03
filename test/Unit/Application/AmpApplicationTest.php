<?php

namespace Labrador\Http\Test\Unit\Application;

use Amp\Http\Cookie\RequestCookie;
use Amp\Http\HttpStatus;
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
use Labrador\Http\Application\AmpApplication;
use Labrador\Http\Application\ApplicationFeatures;
use Labrador\Http\Application\NoApplicationFeatures;
use Labrador\Http\Event\AddRoutes;
use Labrador\Http\Event\ApplicationStarted;
use Labrador\Http\Event\ApplicationStopped;
use Labrador\Http\Event\ReceivingConnections;
use Labrador\Http\Event\RequestReceived;
use Labrador\Http\Event\ResponseSent;
use Labrador\Http\Event\WillInvokeController;
use Labrador\Http\Exception\SessionNotEnabled;
use Labrador\Http\HttpMethod;
use Labrador\Http\Middleware\Priority;
use Labrador\Http\RequestAttribute;
use Labrador\Http\Router\FastRouteRouter;
use Labrador\Http\Router\GetMapping;
use Labrador\Http\Test\Unit\Stub\ErrorHandlerFactoryStub;
use Labrador\Http\Test\Unit\Stub\EventEmitterStub;
use Labrador\Http\Test\Unit\Stub\HttpServerStub;
use Labrador\Http\Test\Unit\Stub\RequireAccessReadSessionController;
use Labrador\Http\Test\Unit\Stub\RequireAccessWriteSessionController;
use Labrador\Http\Test\Unit\Stub\ResponseControllerStub;
use Labrador\Http\Test\Unit\Stub\SessionGatheringController;
use Labrador\HttpDummyApp\Middleware\BarMiddleware;
use Labrador\HttpDummyApp\MiddlewareCallRegistry;
use League\Uri\Http;
use Monolog\Handler\TestHandler;
use Monolog\Logger;
use Monolog\Processor\PsrLogMessageProcessor;
use ParagonIE\ConstantTime\Base64UrlSafe;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
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
            new Logger('labrador-http-test', [$this->testHandler], [new PsrLogMessageProcessor()]),
            new NoApplicationFeatures()
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
        };
    }

    private function getApplicationFeaturesWithHttpToHttpsRedirect() : ApplicationFeatures {
        return new class implements ApplicationFeatures {

            public function getSessionMiddleware() : ?SessionMiddleware {
                return null;
            }

            public function autoRedirectHttpToHttps() : bool {
                return true;
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
            $this->getApplicationFeaturesWithSessionMiddleware()
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
            $this->getApplicationFeaturesWithSessionMiddleware()
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

    public function testControllerRequireSessionReadAccess() : void {
        $controller = new RequireAccessReadSessionController();
        $this->router->addRoute(
            new GetMapping('/session-test'),
            $controller
        );
        $request = new Request(
            $this->getMockBuilder(Client::class)->getMock(),
            HttpMethod::Get->value,
            Http::createFromString('http://example.com/session-test')
        );

        $middleware = new class implements Middleware {
            public function handleRequest(Request $request, RequestHandler $requestHandler) : Response {
                $session = $request->getAttribute(Session::class);
                assert($session instanceof Session);
                $session->open()->set('known-session-path', 'my known value');
                $session->save();

                return $requestHandler->handleRequest($request);
            }
        };

        $subject = new AmpApplication(
            $this->httpServer,
            new ErrorHandlerFactoryStub($this->errorHandler),
            $this->router,
            $this->emitter,
            new Logger('labrador-http-test', [$this->testHandler], [new PsrLogMessageProcessor()]),
            $this->getApplicationFeaturesWithSessionMiddleware()
        );

        $subject->addMiddleware($middleware);

        $subject->start();

        $response = $subject->handleRequest($request);

        self::assertSame('OK', $response->getBody()->read());
        self::assertSame('my known value', $controller->getSessionValue());
    }

    public function testControllerRequireSessionWriteAccess() : void {
        $controller = new RequireAccessWriteSessionController();
        $this->router->addRoute(
            new GetMapping('/session-test'),
            $controller
        );
        $request = new Request(
            $this->getMockBuilder(Client::class)->getMock(),
            HttpMethod::Get->value,
            Http::createFromString('http://example.com/session-test')
        );

        $id = Base64UrlSafe::encode(\random_bytes(36));
        $request->setCookie(new RequestCookie('session', $id));

        $storage = new LocalSessionStorage();

        $middleware = new class implements Middleware {

            private ?string $sessionValue = null;

            public function handleRequest(Request $request, RequestHandler $requestHandler) : Response {
                $session = $request->getAttribute(Session::class);
                assert($session instanceof Session);

                $session->open()->set('known-session-path', 'my known value');
                $session->save();

                $session->unlockAll();

                $response = $requestHandler->handleRequest($request);

                $this->sessionValue = $session->get('known-session-path');

                return $response;
            }

            public function getSessionValue() : ?string {
                return $this->sessionValue;
            }
        };

        $subject = new AmpApplication(
            $this->httpServer,
            new ErrorHandlerFactoryStub($this->errorHandler),
            $this->router,
            $this->emitter,
            new Logger('labrador-http-test', [$this->testHandler], [new PsrLogMessageProcessor()]),
            $this->getApplicationFeaturesWithSessionMiddleware($storage)
        );

        $subject->addMiddleware($middleware);

        $subject->start();

        $response = $subject->handleRequest($request);

        self::assertSame('OK', $response->getBody()->read());
        self::assertSame('prefixed_my known value', $middleware->getSessionValue());
        self::assertSame([
            'known-session-path' => 'prefixed_my known value'
        ], $storage->read($id));
    }

    public function testRequiresSessionReadSessionFactoryNotProvidedThrowsException() : void {
        $controller = new RequireAccessReadSessionController();
        $this->router->addRoute(
            new GetMapping('/'),
            $controller
        );

        $request = new Request(
            $this->getMockBuilder(Client::class)->getMock(),
            HttpMethod::Get->value,
            Http::createFromString('http://example.com/')
        );

        $this->subject->start();

        $this->expectException(SessionNotEnabled::class);
        $this->expectExceptionMessage(sprintf(
            'The Controller "%s" requires Read Session access but Session support has not been enabled. Please ensure ' .
            'that you have configured a SessionFactory in your implemented ApplicationFeatures.',
            $controller->toString()
        ));

        $this->subject->handleRequest($request);
    }

    public function testApplicationFeaturesRedirectHttpToHttps() {
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
            $this->getApplicationFeaturesWithHttpToHttpsRedirect()
        );

        $subject->start();

        $response = $subject->handleRequest($request);

        self::assertSame(
            HttpStatus::SEE_OTHER,
            $response->getStatus()
        );
        self::assertSame(
            ['location' => ['https://example.com/tls-test?foo=bar']],
            $response->getHeaders()
        );
    }

    public function testNormalProcessingHasCorrectRequestAnalyticsQueued() : void {

    }

}
