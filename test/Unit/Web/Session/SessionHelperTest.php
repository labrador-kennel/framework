<?php declare(strict_types=1);

namespace Labrador\Test\Unit\Web\Session;

use Amp\Http\Server\Driver\Client;
use Amp\Http\Server\Request;
use Amp\Http\Server\Session\LocalSessionStorage;
use Amp\Http\Server\Session\Session;
use Amp\Http\Server\Session\SessionFactory;
use Amp\Http\Server\Session\SessionStorage;
use Labrador\TestHelper\KnownSessionIdGenerator;
use Labrador\Web\Session\Exception\SessionNotAttachedToRequest;
use Labrador\Web\Session\SessionAttribute;
use Labrador\Web\Session\SessionHelper;
use League\Uri\Http;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

#[CoversClass(SessionHelper::class)]
#[CoversClass(SessionNotAttachedToRequest::class)]
final class SessionHelperTest extends TestCase {

    private SessionHelper $subject;
    private Session $session;
    private SessionStorage $storage;
    private Request $request;
    private SessionAttribute&MockObject $sessionAttribute;

    protected function setUp() : void {
        $this->subject = new SessionHelper();
        $this->request = new Request(
            $this->createMock(Client::class),
            'GET',
            Http::new('http://example.com')
        );
        $this->storage = new LocalSessionStorage();
        $this->session = (new SessionFactory(
            storage: $this->storage,
            idGenerator: $generator = new KnownSessionIdGenerator()
        ))->create($generator->generate());
        $this->sessionAttribute = $this->createMock(SessionAttribute::class);
        $this->sessionAttribute->expects($this->any())->method('key')->willReturn('session-attr-key');
    }

    public static function sessionNotSetProvider() : array {
        return [
            'id' => [
                function() {
                    return $this->subject->id($this->request);
                }
            ],
            'get' => [
                function() {
                    return $this->subject->get($this->request, $this->sessionAttribute);
                }
            ],
            'getAll' => [
                function() {
                    return $this->subject->getAll($this->request);
                }
            ],
            'has' => [
                function() {
                    return $this->subject->has($this->request, $this->sessionAttribute);
                }
            ],
            'set' => [
                function() {
                    $this->subject->set($this->request, $this->sessionAttribute, 'my-user-id');
                }
            ],
            'unset' => [
                function () {
                    $this->subject->unset($this->request, $this->sessionAttribute);
                }
            ],
            'read' => [
                function() {
                    $this->subject->read($this->request);
                }
            ],
            'isRead' => [
                function() {
                    return $this->subject->isRead($this->request);
                }
            ],
            'lock' => [
                function() {
                    $this->subject->lock($this->request);
                }
            ],
            'isLocked' => [
                function() {
                    return $this->subject->isLocked($this->request);
                }
            ],
            'commit' => [
                function() {
                    $this->subject->commit($this->request);
                }
            ],
            'rollback' => [
                function() {
                    $this->subject->rollback($this->request);
                }
            ]
        ];
    }

    #[DataProvider('sessionNotSetProvider')]
    public function testAnyMethodWithoutSessionSetOnRequestThrowsException(\Closure $callback) : void {
        $this->expectException(SessionNotAttachedToRequest::class);
        $this->expectExceptionMessage(
            'Attempted to access a session that has not been attached to the Request. Please ensure middleware '
            . 'that will attach Session to Request runs first.'
        );

        $callback->bindTo($this, $this)();
    }

    public function testGetWithSessionSetOnRequestButKeyNotPresentReturnsNull() : void {
        $this->request->setAttribute(Session::class, $this->session);

        self::assertNull(
            $this->subject->get($this->request, $this->sessionAttribute)
        );
    }

    public function testGetWithSessionSetOnRequestAndKeyIsPresentReturnsValue() : void {
        $this->request->setAttribute(Session::class, $this->session);
        $this->storage->write('known-session-id-0', [
            'session-attr-key' => 'my-user-id'
        ]);

        self::assertSame(
            'my-user-id',
            $this->subject->get($this->request, $this->sessionAttribute)
        );
    }

    public function testSetWithSessionSetOnRequestAddsCorrectValueToStorage() : void {
        $this->request->setAttribute(Session::class, $this->session);
        $this->session->lock();
        $this->subject->set($this->request, $this->sessionAttribute, 'my-new-user-id');
        $this->session->commit();

        $actual = $this->storage->read('known-session-id-0')['session-attr-key'] ?? null;

        self::assertSame('my-new-user-id', $actual);
    }

    public function testUnsetWithSessionSetOnRequestRemovesValueFromStorage() : void {
        $this->storage->write('known-session-id-0', [
            'session-attr-key' => 'my-user-id'
        ]);
        $this->request->setAttribute(Session::class, $this->session);

        self::assertTrue($this->session->has('session-attr-key'));

        $this->session->lock();
        $this->subject->unset($this->request, $this->sessionAttribute);
        $this->session->commit();

        self::assertFalse($this->session->has('session-attr-key'));
    }

    public function testDestroyingSessionRemovesStorageData() : void {
        $this->storage->write('known-session-id-0', [
            'session-attr-key' => 'my-user-id',
            'some-other-key' => 'some-other-data'
        ]);
        $this->request->setAttribute(Session::class, $this->session);

        self::assertTrue($this->session->has('session-attr-key'));

        $this->session->lock();
        $this->subject->destroy($this->request);

        $this->session->read();

        self::assertSame([], $this->session->getData());
    }

    public function testRegeneratingSessionCreatesNewIdWithDataPersisted() : void {
        $this->storage->write('known-session-id-0', [
            'session-attr-key' => 'my-user-id',
            'some-other-key' => 'some-other-data'
        ]);
        $this->request->setAttribute(Session::class, $this->session);

        self::assertTrue($this->session->has('session-attr-key'));

        $this->session->lock();
        $this->subject->regenerate($this->request);
        $this->session->commit();

        self::assertSame('known-session-id-1', $this->session->getId());
        self::assertSame([
            'session-attr-key' => 'my-user-id',
            'some-other-key' => 'some-other-data'
        ], $this->session->getData());

        self::assertSame([], $this->storage->read('known-session-id-0'));
        self::assertSame([
            'session-attr-key' => 'my-user-id',
            'some-other-key' => 'some-other-data'
        ], $this->storage->read('known-session-id-1'));
    }

    public function testIdReturnsTheSessionId() : void {
        $this->request->setAttribute(Session::class, $this->session);

        self::assertSame('known-session-id-0', $this->subject->id($this->request));
    }

    public function testHasKeyReturnsFalseIfNoKeyPresent() : void {
        $this->storage->write('known-session-id-0', [
            'some-other-key' => 'some-other-data'
        ]);
        $this->request->setAttribute(Session::class, $this->session);

        $this->session->read();

        self::assertFalse($this->subject->has($this->request, $this->sessionAttribute));
    }

    public function testHasKeyReturnsTrueIfKeyPresent() : void {
        $this->storage->write('known-session-id-0', [
            'session-attr-key' => 'my-user-id',
            'some-other-key' => 'some-other-data'
        ]);
        $this->request->setAttribute(Session::class, $this->session);

        $this->session->read();

        self::assertTrue($this->subject->has($this->request, $this->sessionAttribute));
    }

    public function testGetAllReturnsAllDataPresentForSession() : void {
        $this->storage->write('known-session-id-0', [
            'session-attr-key' => 'my-user-id',
            'some-other-key' => 'some-other-data'
        ]);
        $this->request->setAttribute(Session::class, $this->session);

        $this->session->read();

        self::assertSame(
            [
                'session-attr-key' => 'my-user-id',
                'some-other-key' => 'some-other-data'
            ],
            $this->subject->getAll($this->request)
        );
    }

    public function testReadMarksSessionAsRead() : void {
        $this->request->setAttribute(Session::class, $this->session);

        $this->subject->read($this->request);
        self::assertTrue($this->subject->isRead($this->request));
    }

    public function testIsReadMarkedFalseBeforeRead() : void {
        $this->request->setAttribute(Session::class, $this->session);

        self::assertFalse($this->subject->isRead($this->request));
    }

    public function testLockMarksSessionAsLocked() : void {
        $this->request->setAttribute(Session::class, $this->session);

        $this->subject->lock($this->request);
        self::assertTrue($this->subject->isLocked($this->request));
    }

    public function testIsLockedMarkedFalseBeforeLock() : void {
        $this->request->setAttribute(Session::class, $this->session);

        self::assertFalse($this->subject->isLocked($this->request));
    }

    public function testCommitWritesDataToSessionStorage() : void {
        $this->request->setAttribute(Session::class, $this->session);
        $this->subject->lock($this->request);
        $this->subject->set($this->request, $this->sessionAttribute, 'session-value');
        $this->subject->commit($this->request);

        self::assertSame([
            'session-attr-key' => 'session-value'
        ], $this->storage->read('known-session-id-0'));
    }

    public function testRollbackDoesNotWriteDataToSessionStorage() : void {
        $this->request->setAttribute(Session::class, $this->session);
        $this->subject->lock($this->request);
        $this->subject->set($this->request, $this->sessionAttribute, 'session-value');
        $this->subject->rollback($this->request);

        self::assertSame([], $this->subject->getAll($this->request));
    }
}
