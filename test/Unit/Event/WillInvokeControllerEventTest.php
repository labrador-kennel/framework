<?php

namespace Labrador\Http\Test\Unit\Event;

use Labrador\Http\Application\ApplicationEvent;
use Labrador\Http\Controller\Controller;
use Labrador\Http\Event\WillInvokeController;
use Labrador\Http\RequestAttribute;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;

class WillInvokeControllerEventTest extends TestCase {

    private Controller&MockObject $controller;
    private WillInvokeController $subject;
    private UuidInterface $uuid;

    protected function setUp() : void {
        parent::setUp();
        $this->controller = $this->getMockBuilder(Controller::class)->getMock();
        $this->uuid = Uuid::uuid6();
        $this->subject = new WillInvokeController($this->controller, $this->uuid);
    }

    public function testGetName() : void {
        self::assertSame(ApplicationEvent::WillInvokeController->value, $this->subject->getName());
    }

    public function testGetTarget() : void {
        self::assertSame($this->controller, $this->subject->getTarget());
    }

    public function testGetData() : void {
        self::assertSame([
            RequestAttribute::RequestId->value => $this->uuid
        ], $this->subject->getData());
    }

    public function testGetCreatedAt() : void {
        $createdAt = $this->subject->getCreatedAt();

        $diff = $createdAt->diff(new \DateTimeImmutable());

        // Just make sure the datetime was created recently, i.e. within the last second.
        self::assertSame(0, $diff->s);
    }

}