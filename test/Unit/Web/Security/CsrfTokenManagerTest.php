<?php declare(strict_types=1);

namespace Labrador\Test\Unit\Web\Security;

use Amp\Http\Server\Driver\Client;
use Amp\Http\Server\Request;
use Amp\Http\Server\Session\Base64UrlSessionIdGenerator;
use Amp\Http\Server\Session\LocalSessionStorage;
use Amp\Http\Server\Session\Session;
use Amp\Http\Server\Session\SessionStorage;
use Amp\Sync\LocalKeyedMutex;
use Labrador\Security\TokenGenerator;
use Labrador\Web\Exception\SessionNotEnabled;
use Labrador\Web\Security\CsrfTokenManager;
use League\Uri\Http;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

final class CsrfTokenManagerTest extends TestCase {

    private readonly CsrfTokenManager $subject;

    private readonly TokenGenerator&MockObject $tokenGenerator;

    private readonly Request $request;

    private readonly Session $session;

    private readonly SessionStorage $storage;

    private readonly string $sessionId;

    protected function setUp() : void {
        $this->tokenGenerator = $this->getMockBuilder(TokenGenerator::class)->getMock();
        $this->request = new Request(
            $this->getMockBuilder(Client::class)->getMock(),
            'GET',
            Http::createFromString('https://example.com')
        );
        $idGenerator = new Base64UrlSessionIdGenerator();
        $this->session = new Session(
            new LocalKeyedMutex(),
            $this->storage = new LocalSessionStorage(),
            $idGenerator,
            $this->sessionId = $idGenerator->generate()
        );
        $this->subject = new CsrfTokenManager($this->tokenGenerator);
    }

    public function testGenerateAndStoreWithoutSessionThrowsException() : void {
        $this->tokenGenerator->expects($this->never())->method('generateToken');

        $this->expectException(SessionNotEnabled::class);
        $this->expectExceptionMessage('The CsrfTokenManager requires a session be enabled for a request.');

        $this->subject->generateAndStore($this->request);
    }

    public function testGenerateAndStoreWithSessionAndNoCsrfTokensStartsCollectionAndSavesId() : void {
        $this->request->setAttribute(Session::class, $this->session);
        $this->tokenGenerator->expects($this->once())
            ->method('generateToken')
            ->willReturn('known-token');

        $this->session->lock();
        $token = $this->subject->generateAndStore($this->request);
        $this->session->commit();

        $data = $this->storage->read($this->sessionId);
        self::assertArrayHasKey('csrfTokens', $data);
        self::assertJson($data['csrfTokens']);

        $tokens = json_decode($data['csrfTokens'], true);
        self::assertCount(1, $tokens);
        self::assertSame('known-token', $tokens[0]);
        self::assertSame('known-token', $token);
    }

    public function testGenerateAndStoreWithSessionAndExistingCsrfTokenAddsToStore() : void {
        $this->request->setAttribute(Session::class, $this->session);
        $this->tokenGenerator->expects($this->once())
            ->method('generateToken')
            ->willReturn('known-token');
        $this->storage->write($this->sessionId, ['csrfTokens' => json_encode(['existing-token'])]);

        $this->session->lock();
        $token = $this->subject->generateAndStore($this->request);
        $this->session->commit();

        $data = $this->storage->read($this->sessionId);
        self::assertArrayHasKey('csrfTokens', $data);
        self::assertJson($data['csrfTokens']);

        $tokens = json_decode($data['csrfTokens'], true);
        self::assertCount(2, $tokens);
        self::assertSame('existing-token', $tokens[0]);
        self::assertSame('known-token', $tokens[1]);
        self::assertSame('known-token', $token);
    }

    public function testValidateAndExpireWithoutSessionThrowsException() : void {
        $this->expectException(SessionNotEnabled::class);
        $this->expectExceptionMessage('The CsrfTokenManager requires a session be enabled for a request.');

        $this->subject->validateAndExpire($this->request, 'known-token');
    }

    public function testValidateAndExpireWithNoTokensInSessionReturnsFalse() : void {
        $this->request->setAttribute(Session::class, $this->session);

        $this->session->lock();
        $valid = $this->subject->validateAndExpire($this->request, 'known-token');
        $this->session->commit();

        self::assertFalse($valid);
    }

    public function testValidateAndExpireWithValidTokenInSessionReturnsTrueAndRemovesToken() : void {
        $this->request->setAttribute(Session::class, $this->session);
        $this->storage->write($this->sessionId, ['csrfTokens' => json_encode(['known-token'])]);

        $this->session->lock();
        $valid = $this->subject->validateAndExpire($this->request, 'known-token');
        $this->session->commit();

        self::assertTrue($valid);
        self::assertSame(
            ['csrfTokens' => '[]'],
            $this->storage->read($this->sessionId)
        );
    }

    public function testValidateAndExpireWithSessionIdButNoTokensInCacheReturnsFalseAndHasNoStore() : void {
        $this->request->setAttribute(Session::class, $this->session);

        $this->session->lock();
        $valid = $this->subject->validateAndExpire($this->request, 'known-token');
        $this->session->commit();

        self::assertFalse($valid);
        self::assertSame(
            [],
            $this->storage->read($this->sessionId)
        );
    }

    public function testValidateAndExpireWithSessionIdHasCsrfStoreIdRemovedFromSession() : void {
        $this->request->setAttribute(Session::class, $this->session);
        $this->storage->write($this->sessionId, ['csrfTokens' => json_encode(['known-token'])]);

        $this->session->lock();
        $valid = $this->subject->validateAndExpire($this->request, 'known-token');
        $this->session->commit();

        self::assertTrue($valid);
        self::assertSame(
            ['csrfTokens' => json_encode([])],
            $this->storage->read($this->sessionId)
        );
    }

    public function testValidateAndExpireWithMultipleStoreIdsHandledCorrectly() : void {
        $this->request->setAttribute(Session::class, $this->session);
        $this->storage->write($this->sessionId, ['csrfTokens' => json_encode(['other-existing-token', 'known-token'])]);

        $this->session->lock();
        $valid = $this->subject->validateAndExpire($this->request, 'known-token');
        $this->session->commit();

        self::assertTrue($valid);
        self::assertSame(
            ['csrfTokens' => json_encode(['other-existing-token'])],
            $this->storage->read($this->sessionId)
        );
    }
}
