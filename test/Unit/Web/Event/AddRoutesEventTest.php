<?php

namespace Labrador\Test\Unit\Web\Event;

use Labrador\Web\Application\ApplicationEvent;
use Labrador\Web\Application\Event\AddRoutes;
use Labrador\Web\Middleware\GlobalMiddlewareCollection;
use Labrador\Web\Router\Router;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class AddRoutesEventTest extends TestCase {

    private Router&MockObject $router;
    private GlobalMiddlewareCollection $globalMiddlewareCollection;
    private AddRoutes $subject;

    protected function setUp() : void {
        parent::setUp();
        $this->router = $this->getMockBuilder(Router::class)->getMock();
        $this->subject = new AddRoutes($this->router);
    }

    public function testGetName() : void {
        self::assertSame(ApplicationEvent::AddRoutes->value, $this->subject->name());
    }

    public function testGetTarget() : void {
        self::assertSame($this->router, $this->subject->payload());
    }

    public function testGetCreatedAt() : void {
        $createdAt = $this->subject->triggeredAt();

        $diff = $createdAt->diff(new \DateTimeImmutable());

        // Just make sure the datetime was created recently, i.e. within the last second.
        self::assertSame(0, $diff->s);
    }
}
