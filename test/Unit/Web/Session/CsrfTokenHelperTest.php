<?php declare(strict_types=1);

namespace Labrador\Test\Unit\Web\Session;

use Amp\Http\Server\Driver\Client;
use Amp\Http\Server\Request;
use Amp\Http\Server\Session\LocalSessionStorage;
use Amp\Http\Server\Session\Session;
use Amp\Sync\LocalKeyedMutex;
use Labrador\Web\Session\CsrfTokenHelper;
use Labrador\Web\Session\Exception\SessionHasNoCsrfToken;
use Labrador\Web\Session\Exception\SessionNotAttachedToRequest;
use Labrador\Web\TestHelper\KnownSessionIdGenerator;
use League\Uri\Http;
use PHPUnit\Framework\TestCase;

final class CsrfTokenHelperTest extends TestCase {

    private Client $client;

    protected function setUp() : void {
        $this->client = $this->createMock(Client::class);
    }

    public function testGetTokenFromRequestWithNoSessionThrowsException() : void {
        $this->expectException(SessionNotAttachedToRequest::class);
        $this->expectExceptionMessage(
            'Attempted to access a session that has not been attached to the Request. Please ensure '
            . 'middleware that will attach Session to Request runs first.'
        );

        (new CsrfTokenHelper())->token(new Request($this->client, 'GET', Http::new('http://example.com')));
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

        (new CsrfTokenHelper())->token($request);
    }

    public function testGetTokenFromRequestWhenAttachedSessionDoesHaveCsrfToken() : void {
        $storage = new LocalSessionStorage();
        $storage->write('known-session-id', ['labrador.csrfToken' => 'my-stored-token']);
        $request = new Request($this->client, 'GET', Http::new('http://example.com'));
        $request->setAttribute(Session::class, $session = new Session(
            new LocalKeyedMutex(),
            $storage,
            new KnownSessionIdGenerator(),
            'known-session-id'
        ));

        $session->lock();

        $actual = (new CsrfTokenHelper())->token($request);

        $session->unlock();

        self::assertSame('my-stored-token', $actual);
    }

    public function testStoredTokenMatchesCheckedTokenReturnsIsValid() : void {
        $storage = new LocalSessionStorage();
        $storage->write('known-session-id', ['labrador.csrfToken' => 'my-stored-token']);
        $request = new Request($this->client, 'GET', Http::new('http://example.com'));
        $request->setAttribute(Session::class, $session = new Session(
            new LocalKeyedMutex(),
            $storage,
            new KnownSessionIdGenerator(),
            'known-session-id'
        ));

        $session->lock();

        self::assertTrue((new CsrfTokenHelper())->isTokenValid($request, 'my-stored-token'));
    }

    public function testStoredTokenDoesNotMatchCheckedTokenReturnsIsNotValid() : void {
        $storage = new LocalSessionStorage();
        $storage->write('known-session-id', ['labrador.csrfToken' => 'my-stored-token']);
        $request = new Request($this->client, 'GET', Http::new('http://example.com'));
        $request->setAttribute(Session::class, $session = new Session(
            new LocalKeyedMutex(),
            $storage,
            new KnownSessionIdGenerator(),
            'known-session-id'
        ));

        $session->lock();

        self::assertFalse((new CsrfTokenHelper())->isTokenValid($request, 'my-stored-token-wrong'));
    }
}
