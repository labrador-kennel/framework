<?php

namespace Labrador\Test\Unit\Web\Event;

use Labrador\Web\Application\ApplicationEvent;
use Labrador\Web\Event\AddRoutes;
use Labrador\Web\Router\Router;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class AddRoutesEventTest extends TestCase {

    private Router&MockObject $router;
    private AddRoutes $subject;

    protected function setUp() : void {
        parent::setUp();
        $this->router = $this->getMockBuilder(Router::class)->getMock();
        $this->subject = new AddRoutes($this->router);
    }

    public function testGetName() : void {
        self::assertSame(ApplicationEvent::AddRoutes->value, $this->subject->getName());
    }

    public function testGetTarget() : void {
        self::assertSame($this->router, $this->subject->getTarget());
    }

    public function testGetData() : void {
        self::assertSame([], $this->subject->getData());
    }

    public function testGetCreatedAt() : void {
        $createdAt = $this->subject->getCreatedAt();

        $diff = $createdAt->diff(new \DateTimeImmutable());

        // Just make sure the datetime was created recently, i.e. within the last second.
        self::assertSame(0, $diff->s);
    }

}