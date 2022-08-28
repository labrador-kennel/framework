<?php

namespace Cspray\Labrador\Http\Test\Unit\Event;

use Cspray\Labrador\Http\Application;
use Cspray\Labrador\Http\ApplicationEvent;
use Cspray\Labrador\Http\Event\ApplicationStartedEvent;
use Cspray\Labrador\Http\Event\ApplicationStoppedEvent;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class ApplicationStoppedEventTest extends TestCase {

    private Application&MockObject $app;
    private ApplicationStoppedEvent $subject;

    protected function setUp() : void {
        parent::setUp();
        $this->app = $this->getMockBuilder(Application::class)->getMock();
        $this->subject = new ApplicationStoppedEvent($this->app);
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