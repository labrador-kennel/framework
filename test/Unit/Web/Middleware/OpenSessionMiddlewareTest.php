<?php declare(strict_types=1);

namespace Labrador\Test\Unit\Web\Middleware;

use Amp\Http\Server\Driver\Client;
use Amp\Http\Server\Request;
use Amp\Http\Server\RequestHandler;
use Amp\Http\Server\Response;
use Amp\Http\Server\Session\Base64UrlSessionIdGenerator;
use Amp\Http\Server\Session\DefaultSessionIdGenerator;
use Amp\Http\Server\Session\LocalSessionStorage;
use Amp\Http\Server\Session\Session;
use Amp\Http\Server\Session\SessionStorage;
use Amp\Sync\LocalKeyedMutex;
use Labrador\Test\Unit\Web\Stub\ResponseControllerStub;
use Labrador\Test\Unit\Web\Stub\SessionDestroyingController;
use Labrador\Web\Exception\SessionNotEnabled;
use Labrador\Web\Middleware\OpenSession;
use League\Uri\Http;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

final class OpenSessionMiddlewareTest extends TestCase {

    private OpenSession $subject;

    private Client&MockObject $client;

    private Session $session;

    private SessionStorage $storage;

    private string $sessionId;

    protected function setUp() : void {
        $this->subject = new OpenSession();
        $this->client = $this->getMockBuilder(Client::class)->getMock();
    }

    public function testRequestDoesNotHaveSessionThrowsException() : void {
        $handler = $this->getMockBuilder(RequestHandler::class)->getMock();
        $handler->expects($this->never())->method('handleRequest');

        $this->expectException(SessionNotEnabled::class);
        $this->expectExceptionMessage('The ' . OpenSession::class . ' was added to a route but no session was found on the request.');

        $this->subject->handleRequest(
            new Request($this->client, 'GET', Http::new('https://example.com')),
            $handler
        );
    }

    public function testRequestDoesHaveSessionOpensItBeforeRequestHandler() : void {
        $this->session = new Session(
            new LocalKeyedMutex(),
            $this->storage = new LocalSessionStorage(),
            $generator = new Base64UrlSessionIdGenerator(),
            $this->sessionId = $generator->generate()
        );

        $request = new Request($this->client, 'GET', Http::new('https://example.com'));
        $request->setAttribute(Session::class, $this->session);

        $handler = $this->getMockBuilder(RequestHandler::class)->getMock();
        $handler->expects($this->once())
            ->method('handleRequest')
            ->with($this->callback(static function(Request $request) {
                $session = $request->getAttribute(Session::class);
                return $session instanceof Session && $session->isRead() && $session->isLocked();
            }))->willReturn(new Response());

        $this->subject->handleRequest($request, $handler);
    }

    public function testRequestDoesHaveSessionSavesToStorageIfControllerWritesData() : void {
        $this->session = new Session(
            new LocalKeyedMutex(),
            $this->storage = new LocalSessionStorage(),
            $generator = new Base64UrlSessionIdGenerator(),
            $this->sessionId = $generator->generate()
        );

        $request = new Request($this->client, 'GET', Http::new('https://example.com'));
        $request->setAttribute(Session::class, $this->session);

        self::assertEmpty($this->storage->read($this->sessionId));

        $handler = new class implements RequestHandler {
            public function handleRequest(Request $request) : Response {
                $request->getAttribute(Session::class)->set('known-key', 'known-value');
                return new Response();
            }
        };

        $this->subject->handleRequest($request, $handler);

        self::assertSame(['known-key' => 'known-value'], $this->storage->read($this->sessionId));
    }

    public function testRequestSessionIsUnlockedAfterControllerInvoked() : void {
        $this->session = new Session(
            new LocalKeyedMutex(),
            $this->storage = new LocalSessionStorage(),
            $generator = new Base64UrlSessionIdGenerator(),
            $this->sessionId = $generator->generate()
        );

        $request = new Request($this->client, 'GET', Http::new('https://example.com'));
        $request->setAttribute(Session::class, $this->session);

        $this->subject->handleRequest($request, new ResponseControllerStub(new Response()));

        self::assertFalse($this->session->isLocked());
    }

    public function testHandleSessionBeingDestroyedDoesNotThrowError() : void {
        $this->session = new Session(
            new LocalKeyedMutex(),
            $this->storage = new LocalSessionStorage(),
            $generator = new Base64UrlSessionIdGenerator(),
            $this->sessionId = $generator->generate()
        );

        $request = new Request($this->client, 'GET', Http::new('https://example.com'));
        $request->setAttribute(Session::class, $this->session);

        $response = $this->subject->handleRequest($request, new SessionDestroyingController());

        self::assertFalse($this->session->isLocked());
        self::assertSame('Session destroyed', $response->getBody()->read());
    }
}
