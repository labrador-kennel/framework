<?php declare(strict_types=1);

namespace Labrador\Test\Unit\Web\Security;

use Amp\Cache\Cache;
use Amp\Cache\LocalCache;
use Amp\Http\Server\Driver\Client;
use Amp\Http\Server\Request;
use Amp\Http\Server\Session\DefaultSessionIdGenerator;
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
use Ramsey\Uuid\Uuid;

final class CsrfTokenManagerTest extends TestCase {

    private readonly CsrfTokenManager $subject;

    private readonly TokenGenerator&MockObject $tokenGenerator;

    private readonly Cache $cache;

    private readonly Request $request;

    private readonly Session $session;

    private readonly SessionStorage $storage;

    private readonly string $sessionId;

    protected function setUp() : void {
        $this->tokenGenerator = $this->getMockBuilder(TokenGenerator::class)->getMock();
        $this->cache = new LocalCache();
        $this->request = new Request(
            $this->getMockBuilder(Client::class)->getMock(),
            'GET',
            Http::createFromString('https://example.com')
        );
        $idGenerator = new DefaultSessionIdGenerator();
        $this->session = new Session(
            new LocalKeyedMutex(),
            $this->storage = new LocalSessionStorage(),
            $idGenerator,
            $this->sessionId = $idGenerator->generate()
        );
        $this->subject = new CsrfTokenManager($this->tokenGenerator, $this->cache);
    }

    public function testGenerateAndStoreWithoutSessionThrowsException() : void {
        $this->tokenGenerator->expects($this->never())->method('generateToken');

        $this->expectException(SessionNotEnabled::class);
        $this->expectExceptionMessage('The CsrfTokenManager requires a session be enabled for a request.');

        $this->subject->generateAndStore($this->request);
    }

    public function testGenerateAndStoreWithSessionAndNoCsrfTokensStartsCollectionAndSavesId() : void {
        $this->request->setAttribute('session', $this->session);
        $this->tokenGenerator->expects($this->once())
            ->method('generateToken')
            ->willReturn('known-token');

        $this->session->open();
        $token = $this->subject->generateAndStore($this->request);
        $this->session->save();

        $data = $this->storage->read($this->sessionId);
        self::assertArrayHasKey('csrfStoreIds', $data);
        self::assertJson($data['csrfStoreIds']);

        $storeIds = json_decode($data['csrfStoreIds'], true);
        self::assertCount(1, $storeIds);
        self::assertSame('known-token', $this->cache->get($storeIds[0]));
        self::assertSame('known-token', $token);
    }

    public function testGenerateAndStoreWithSessionAndExistingCsrfTokenAddsToStore() : void {
        $cacheId = Uuid::uuid4()->toString();
        $this->request->setAttribute('session', $this->session);
        $this->tokenGenerator->expects($this->once())
            ->method('generateToken')
            ->willReturn('known-token');
        $this->cache->set($cacheId, 'existing-token');
        $this->storage->write($this->sessionId, ['csrfStoreIds' => json_encode([$cacheId])]);

        $this->session->open();
        $token = $this->subject->generateAndStore($this->request);
        $this->session->save();

        $data = $this->storage->read($this->sessionId);
        self::assertArrayHasKey('csrfStoreIds', $data);
        self::assertJson($data['csrfStoreIds']);

        $storeIds = json_decode($data['csrfStoreIds'], true);
        self::assertCount(2, $storeIds);
        self::assertSame('existing-token', $this->cache->get($storeIds[0]));
        self::assertSame('known-token', $this->cache->get($storeIds[1]));
        self::assertSame('known-token', $token);
    }

    public function testValidateAndExpireWithoutSessionThrowsException() : void {
        $this->expectException(SessionNotEnabled::class);
        $this->expectExceptionMessage('The CsrfTokenManager requires a session be enabled for a request.');

        $this->subject->validateAndExpire($this->request, 'known-token');
    }

    public function testValidateAndExpireWithNoTokensInSessionReturnsFalse() : void {
        $this->request->setAttribute('session', $this->session);

        $this->session->open();
        $valid = $this->subject->validateAndExpire($this->request, 'known-token');
        $this->session->save();

        self::assertFalse($valid);
    }

    public function testValidateAndExpireWithValidTokenInSessionReturnsTrueAndRemovesToken() : void {
        $id = Uuid::uuid4()->toString();
        $this->request->setAttribute('session', $this->session);
        $this->storage->write($this->sessionId, ['csrfStoreIds' => json_encode([$id])]);
        $this->cache->set($id, 'known-token');

        $this->session->open();
        $valid = $this->subject->validateAndExpire($this->request, 'known-token');
        $this->session->save();

        self::assertTrue($valid);
        self::assertNull($this->cache->get($id));
    }

    public function testValidateAndExpireWithSessionIdButNoTokensInCacheReturnsFalseAndHasNoStore() : void {
        $id = Uuid::uuid4()->toString();
        $this->request->setAttribute('session', $this->session);
        $this->storage->write($this->sessionId, ['csrfStoreIds' => json_encode([$id])]);

        $this->session->open();
        $valid = $this->subject->validateAndExpire($this->request, 'known-token');
        $this->session->save();

        self::assertFalse($valid);
        self::assertNull($this->cache->get($id));
    }

    public function testValidateAndExpireWithSessionIdHasCsrfStoreIdRemovedFromSession() : void {
        $id = Uuid::uuid4()->toString();
        $this->request->setAttribute('session', $this->session);
        $this->storage->write($this->sessionId, ['csrfStoreIds' => json_encode([$id])]);
        $this->cache->set($id, 'known-token');

        $this->session->open();
        $this->subject->validateAndExpire($this->request, 'known-token');
        $this->session->save();

        self::assertSame(
            ['csrfStoreIds' => json_encode([])],
            $this->storage->read($this->sessionId)
        );
    }

    public function testValidateAndExpireWithMultipleStoreIdsHandledCorrectly() : void {
        $id = Uuid::uuid4()->toString();
        $this->request->setAttribute('session', $this->session);
        $this->storage->write($this->sessionId, ['csrfStoreIds' => json_encode([$existingId = Uuid::uuid4()->toString(), $id])]);

        $this->cache->set($existingId, 'other-existing-token');
        $this->cache->set($id, 'known-token');

        $this->session->open();
        $valid = $this->subject->validateAndExpire($this->request, 'known-token');
        $this->session->save();

        self::assertTrue($valid);
        self::assertNull($this->cache->get($id));
        self::assertSame(
            ['csrfStoreIds' => json_encode([$existingId])],
            $this->storage->read($this->sessionId)
        );
    }

}