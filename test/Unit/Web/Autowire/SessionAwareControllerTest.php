<?php declare(strict_types=1);

namespace Labrador\Test\Unit\Web\Autowire;

use Amp\Http\Server\Session\SessionMiddleware;
use Labrador\Web\Autowire\SessionAwareController;
use Labrador\Web\Router\Mapping\RequestMapping;
use Labrador\Web\Session\CsrfAwareSessionMiddleware;
use Labrador\Web\Session\LockAndAutoCommitSessionMiddleware;
use PHPUnit\Framework\TestCase;

final class SessionAwareControllerTest extends TestCase {

    public function testRequestMappingInjectedIsReturned() : void {
        $requestMapping = $this->createMock(RequestMapping::class);
        $subject = new SessionAwareController(
            $requestMapping
        );

        self::assertSame($requestMapping, $subject->requestMapping());
    }

    public function testNameIsAutomaticallyNull() : void {
        $subject = new SessionAwareController(
            $this->createMock(RequestMapping::class)
        );

        self::assertNull($subject->name());
    }

    public function testProfilesInjectedIsReturned() : void {
        $subject = new SessionAwareController(
            $this->createMock(RequestMapping::class),
            profiles: ['my', 'profiles', 'here']
        );

        self::assertSame(['my', 'profiles', 'here'], $subject->profiles());
    }

    public function testIsPrimaryAutomaticallyFalse() : void {
        $subject = new SessionAwareController(
            $this->createMock(RequestMapping::class),
        );

        self::assertFalse($subject->isPrimary());
    }

    public function testNoMiddlewareDefinedImplicitlyIncludesSessionMiddlewareInCorrectOrder() : void {
        $subject = new SessionAwareController(
            $this->createMock(RequestMapping::class)
        );

        self::assertSame(
            [SessionMiddleware::class, CsrfAwareSessionMiddleware::class, LockAndAutoCommitSessionMiddleware::class],
            $subject->middleware()
        );
    }

    public function testWithMiddlewareExplicitlyDefinedTheImplicitSessionMiddlewareAreIncludedFirst() : void {
        $subject = new SessionAwareController(
            $this->createMock(RequestMapping::class),
            middleware: ['MyMiddleware', 'MyOtherMiddleware']
        );

        self::assertSame(
            [
                SessionMiddleware::class,
                CsrfAwareSessionMiddleware::class,
                LockAndAutoCommitSessionMiddleware::class,
                'MyMiddleware',
                'MyOtherMiddleware'
            ],
            $subject->middleware()
        );
    }
}
