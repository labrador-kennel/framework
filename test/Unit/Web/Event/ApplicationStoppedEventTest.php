<?php

namespace Labrador\Test\Unit\Web\Event;

use Labrador\Web\Application\Application;
use Labrador\Web\Application\ApplicationEvent;
use Labrador\Web\Application\Event\ApplicationStopped;
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
        self::assertSame(ApplicationEvent::ApplicationStopped->value, $this->subject->name());
    }

    public function testGetTarget() : void {
        self::assertSame($this->app, $this->subject->payload());
    }

    public function testGetCreatedAt() : void {
        $createdAt = $this->subject->triggeredAt();

        $diff = $createdAt->diff(new \DateTimeImmutable());

        // Just make sure the datetime was created recently, i.e. within the last second.
        self::assertSame(0, $diff->s);
    }
}
