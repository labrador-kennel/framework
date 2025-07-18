<?php declare(strict_types=1);

namespace Labrador\Test\Unit\Web\Session;

use Amp\Http\Server\Driver\Client;
use Amp\Http\Server\Request;
use Amp\Http\Server\Session\LocalSessionStorage;
use Amp\Http\Server\Session\Session;
use Amp\Sync\LocalKeyedMutex;
use Labrador\TestHelper\KnownSessionIdGenerator;
use Labrador\Web\Session\CsrfTokenHelper;
use Labrador\Web\Session\Exception\SessionHasNoCsrfToken;
use Labrador\Web\Session\Exception\SessionNotAttachedToRequest;
use Labrador\Web\Session\SessionHelper;
use League\Uri\Http;
use PHPUnit\Framework\TestCase;

final class CsrfTokenHelperTest extends TestCase {

    private Client $client;
    private SessionHelper $sessionHelper;

    protected function setUp() : void {
        $this->client = $this->createMock(Client::class);
        $this->sessionHelper = new SessionHelper();
    }

    public function testGetTokenFromRequestWithNoSessionThrowsException() : void {
        $this->expectException(SessionNotAttachedToRequest::class);
        $this->expectExceptionMessage(
            'Attempted to access a session that has not been attached to the Request. Please ensure '
            . 'middleware that will attach Session to Request runs first.'
        );

        (new CsrfTokenHelper($this->sessionHelper))->token(
            new Request($this->client, 'GET', Http::new('http://example.com'))
        );
    }

    public function testGetTokenFromRequestWhenAttachedSessionDoesNotHaveCsrfToken() : void {
        $this->expectException(SessionHasNoCsrfToken::class);
        $this->expectExceptionMessage(
            'Attached session has no CSRF token associated with it.'
        );

        $request = new Request($this->client, 'GET', Http::new('http://example.com'));
        $request->setAttribute(Session::class, new Session(
            new LocalKeyedMutex(),
            new LocalSessionStorage(),
            new KnownSessionIdGenerator(),
            null
        ));

        (new CsrfTokenHelper($this->sessionHelper))->token($request);
    }

    public function testGetTokenFromRequestWhenAttachedSessionDoesHaveCsrfToken() : void {
        $generator = new KnownSessionIdGenerator();
        $sessionId = $generator->generate();
        $storage = new LocalSessionStorage();
        $storage->write($sessionId, ['labrador.csrfToken' => 'my-stored-token']);
        $request = new Request($this->client, 'GET', Http::new('http://example.com'));
        $request->setAttribute(Session::class, $session = new Session(
            new LocalKeyedMutex(),
            $storage,
            $generator,
            $sessionId
        ));

        $session->lock();

        $actual = (new CsrfTokenHelper($this->sessionHelper))->token($request);

        $session->unlock();

        self::assertSame('my-stored-token', $actual);
    }

    public function testStoredTokenMatchesCheckedTokenReturnsIsValid() : void {
        $generator = new KnownSessionIdGenerator();
        $sessionId = $generator->generate();
        $storage = new LocalSessionStorage();
        $storage->write($sessionId, ['labrador.csrfToken' => 'my-stored-token']);
        $request = new Request($this->client, 'GET', Http::new('http://example.com'));
        $request->setAttribute(Session::class, $session = new Session(
            new LocalKeyedMutex(),
            $storage,
            $generator,
            $sessionId
        ));

        $session->lock();

        self::assertTrue((new CsrfTokenHelper($this->sessionHelper))->isTokenValid($request, 'my-stored-token'));
    }

    public function testStoredTokenDoesNotMatchCheckedTokenReturnsIsNotValid() : void {
        $generator = new KnownSessionIdGenerator();
        $sessionId = $generator->generate();
        $storage = new LocalSessionStorage();
        $storage->write($sessionId, ['labrador.csrfToken' => 'my-stored-token']);
        $request = new Request($this->client, 'GET', Http::new('http://example.com'));
        $request->setAttribute(Session::class, $session = new Session(
            new LocalKeyedMutex(),
            $storage,
            $generator,
            $sessionId
        ));

        $session->lock();

        self::assertFalse(
            (new CsrfTokenHelper($this->sessionHelper))->isTokenValid($request, 'my-stored-token-wrong')
        );
    }
}
