<?php

namespace Labrador\Test\Unit\Web\Event;

use Amp\Http\Server\Driver\Client;
use Amp\Http\Server\Request;
use Labrador\Web\Application\ApplicationEvent;
use Labrador\Web\Application\Event\WillInvokeController;
use Labrador\Web\Controller\Controller;
use League\Uri\Http;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class WillInvokeControllerEventTest extends TestCase {

    private Controller&MockObject $controller;
    private WillInvokeController $subject;
    private Request $request;

    protected function setUp() : void {
        parent::setUp();
        $this->controller = $this->getMockBuilder(Controller::class)->getMock();
        $this->request = new Request($this->getMockBuilder(Client::class)->getMock(), 'GET', Http::new('http://example.com'));
        $this->subject = new WillInvokeController($this->controller, $this->request);
    }

    public function testGetName() : void {
        self::assertSame(ApplicationEvent::WillInvokeController->value, $this->subject->name());
    }

    public function testGetTarget() : void {
        self::assertSame($this->controller, $this->subject->payload()->controller());
        self::assertSame($this->request, $this->subject->payload()->request());
    }

    public function testGetCreatedAt() : void {
        $createdAt = $this->subject->triggeredAt();

        $diff = $createdAt->diff(new \DateTimeImmutable());

        // Just make sure the datetime was created recently, i.e. within the last second.
        self::assertSame(0, $diff->s);
    }

}