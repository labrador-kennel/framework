<?php

namespace Labrador\Http\Test\Unit\Event;

use Labrador\Http\Application\Application;
use Labrador\Http\Application\ApplicationEvent;
use Labrador\Http\Event\ApplicationStopped;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class ApplicationStoppedEventTest extends TestCase {

    private Application&MockObject $app;
    private ApplicationStopped $subject;

    protected function setUp() : void {
        parent::setUp();
        $this->app = $this->getMockBuilder(Application::class)->getMock();
        $this->subject = new ApplicationStopped($this->app);
    }

    public function testGetName() : void {
        self::assertSame(ApplicationEvent::ApplicationStopped->value, $this->subject->getName());
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