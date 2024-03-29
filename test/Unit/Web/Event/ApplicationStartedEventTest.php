<?php

namespace Labrador\Test\Unit\Web\Event;

use Labrador\Web\Application\Application;
use Labrador\Web\Application\ApplicationEvent;
use Labrador\Web\Application\Event\ApplicationStarted;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class ApplicationStartedEventTest extends TestCase {

    private Application&MockObject $app;
    private ApplicationStarted $subject;

    protected function setUp() : void {
        parent::setUp();
        $this->app = $this->getMockBuilder(Application::class)->getMock();
        $this->subject = new ApplicationStarted($this->app);
    }

    public function testGetName() : void {
        self::assertSame(ApplicationEvent::ApplicationStarted->value, $this->subject->getName());
    }

    public function testGetTarget() : void {
        self::assertSame($this->app, $this->subject->getTarget());
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