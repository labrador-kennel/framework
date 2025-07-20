<?php declare(strict_types=1);

namespace Labrador\Test\Unit\Web\Session;

use Amp\Http\Cookie\RequestCookie;
use Amp\Http\Server\Driver\Client;
use Amp\Http\Server\Middleware;
use Amp\Http\Server\Request;
use Amp\Http\Server\Response;
use Amp\Http\Server\Session\LocalSessionStorage;
use Amp\Http\Server\Session\Session;
use Amp\Http\Server\Session\SessionFactory;
use Amp\Http\Server\Session\SessionMiddleware;
use Labrador\Security\TokenGenerator;
use Labrador\Test\Unit\Web\Stub\ResponseControllerStub;
use Labrador\TestHelper\KnownSessionIdGenerator;
use Labrador\Web\Session\CsrfAwareSessionMiddleware;
use Labrador\Web\Session\Exception\SessionNotAttachedToRequest;
use Labrador\Web\Session\SessionHelper;
use League\Uri\Http;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

final class CsrfAwareSessionMiddlewareTest extends TestCase {

    private Client&MockObject $client;
    private TokenGenerator&MockObject $tokenGenerator;
    private SessionHelper $sessionHelper;

    protected function setUp() : void {
        $this->client = $this->createMock(Client::class);
        $this->tokenGenerator = $this->createMock(TokenGenerator::class);
        $this->sessionHelper = new SessionHelper();
    }

    public function testMiddlewareRequestDoesNotHaveSessionThrowsException() : void {
        $subject = new CsrfAwareSessionMiddleware($this->tokenGenerator, $this->sessionHelper);

        $stack = Middleware\stackMiddleware(
            new ResponseControllerStub(new Response()),
            $subject
        );

        $request = new Request($this->client, 'GET', Http::new('http://example.com'));

        $this->expectException(SessionNotAttachedToRequest::class);
        $this->expectExceptionMessage(
            'Attempted to access a session that has not been attached to the Request. Please ensure middleware '
            . 'that will attach Session to Request runs first.'
        );

        $stack->handleRequest($request);
    }

    public function testMiddlewareSetsCsrfTokenOnSessionAndSessionIsUnlocked() : void {
        $subject = new CsrfAwareSessionMiddleware($this->tokenGenerator, $this->sessionHelper);

        $storage = new LocalSessionStorage();
        $stack = Middleware\stackMiddleware(
            new ResponseControllerStub(new Response()),
            new SessionMiddleware(
                new SessionFactory(storage: $storage)
            ),
            $subject
        );

        $this->tokenGenerator->expects($this->once())
            ->method('generateToken')
            ->willReturn('token');

        $request = new Request($this->client, 'GET', Http::new('http://example.com'));
        $stack->handleRequest($request);

        $session = $request->getAttribute(Session::class);

        self::assertInstanceOf(Session::class, $session);
        self::assertFalse($session->isLocked());
        self::assertNotNull($session->getId());
        self::assertSame(
            ['labrador.csrfToken' => 'token'],
            $storage->read($session->getId())
        );
    }

    public function testSessionWithExistingCsrfTokenDoesNotHaveItRegenerated() : void {
        $subject = new CsrfAwareSessionMiddleware($this->tokenGenerator, $this->sessionHelper);

        $storage = new LocalSessionStorage();
        $stack = Middleware\stackMiddleware(
            new ResponseControllerStub(new Response()),
            new SessionMiddleware(
                new SessionFactory(
                    storage: $storage,
                    idGenerator: $sessionIdGenerator = new KnownSessionIdGenerator()
                )
            ),
            $subject
        );

        $this->tokenGenerator->expects($this->never())
            ->method('generateToken')
            ->willReturn('token');

        $request = new Request($this->client, 'GET', Http::new('http://example.com'));
        $request->setCookie(new RequestCookie('session', $sessionIdGenerator->currentId()));
        $storage->write($sessionIdGenerator->currentId(), ['labrador.csrfToken' => 'existing token']);

        $stack->handleRequest($request);

        $session = $request->getAttribute(Session::class);
        self::assertInstanceOf(Session::class, $session);
        self::assertFalse($session->isLocked());
        self::assertSame($sessionIdGenerator->currentId(), $session->getId());
        self::assertSame(
            ['labrador.csrfToken' => 'existing token'],
            $storage->read($session->getId())
        );
    }
}
