<?php declare(strict_types=1);

namespace Labrador\Test\Unit\Web\Session;

use Amp\Http\Cookie\RequestCookie;
use Amp\Http\Server\Driver\Client;
use Amp\Http\Server\Request;
use Amp\Http\Server\Response;
use Amp\Http\Server\Session\LocalSessionStorage;
use Amp\Http\Server\Session\SessionFactory;
use Amp\Http\Server\Session\SessionMiddleware;
use Labrador\Test\Unit\Web\Stub\ResponseControllerStub;
use Labrador\Test\Unit\Web\Stub\SessionReadingControllerStub;
use Labrador\Test\Unit\Web\Stub\SessionWritingControllerStub;
use Labrador\Web\Session\Exception\SessionNotAttachedToRequest;
use Labrador\Web\Session\LockAndAutoCommitSessionMiddleware;
use Labrador\Web\TestHelper\KnownSessionIdGenerator;
use League\Uri\Http;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use function Amp\Http\Server\Middleware\stackMiddleware;

final class LockAndAutoCommitSessionMiddlewareTest extends TestCase {

    private LockAndAutoCommitSessionMiddleware $subject;
    private Client&MockObject $client;

    protected function setUp() : void {
        $this->subject = new LockAndAutoCommitSessionMiddleware();
        $this->client = $this->createMock(Client::class);
    }

    public function testSessionNotSetOnRequestThrowsException() : void {
        $stack = stackMiddleware(
            new ResponseControllerStub(new Response()),
            $this->subject
        );

        $request = new Request($this->client, 'GET', Http::new('http://example.com'));

        $this->expectException(SessionNotAttachedToRequest::class);
        $this->expectExceptionMessage(
            'Attempted to access a session that has not been attached to the Request. Please ensure middleware '
            . 'that will attach Session to Request runs first.'
        );

        $stack->handleRequest($request);
    }

    public function testAllowsWritingAndReadingAcrossRequestsOnSameSession() : void {
        $storage = new LocalSessionStorage();
        $idGenerator = new KnownSessionIdGenerator();

        $sessionMiddleware = new SessionMiddleware(
            new SessionFactory(storage: $storage, idGenerator: $idGenerator)
        );

        $writeRequest = new Request($this->client, 'GET', Http::new('http://example.com'));
        $writeRequest->setCookie(
            new RequestCookie('session', 'known-session-id')
        );
        stackMiddleware(
            new SessionWritingControllerStub(),
            $sessionMiddleware,
            $this->subject
        )->handleRequest($writeRequest);

        $readRequest = new Request($this->client, 'GET', Http::new('http://example.com'));
        $readRequest->setCookie(
            new RequestCookie('session', 'known-session-id')
        );
        $response = stackMiddleware(
            new SessionReadingControllerStub('known-key'),
            $sessionMiddleware,
            $this->subject
        )->handleRequest($readRequest);

        self::assertSame('known-value', $response->getBody()->read());
        self::assertSame(['known-key' => 'known-value'], $storage->read('known-session-id'));
    }
}
