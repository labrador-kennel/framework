<?php

namespace Labrador\Test\Unit\Web\Event;

use Amp\Http\Server\Driver\Client;
use Amp\Http\Server\Request;
use Amp\Http\Server\RequestHandler;
use Labrador\Web\Application\ApplicationEvent;
use Labrador\Web\Application\Event\WillInvokeRequestHandler;
use League\Uri\Http;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class WillInvokeRequestHandlerEventTest extends TestCase {

    private RequestHandler&MockObject $requestHandler;
    private WillInvokeRequestHandler $subject;
    private Request $request;

    protected function setUp() : void {
        parent::setUp();
        $this->requestHandler = $this->getMockBuilder(RequestHandler::class)->getMock();
        $this->request = new Request($this->getMockBuilder(Client::class)->getMock(), 'GET', Http::new('http://example.com'));
        $this->subject = new WillInvokeRequestHandler($this->requestHandler, $this->request);
    }

    public function testGetName() : void {
        self::assertSame(ApplicationEvent::WillInvokeRequestHandler->value, $this->subject->name());
    }

    public function testGetTarget() : void {
        self::assertSame($this->requestHandler, $this->subject->payload()->requestHandler());
        self::assertSame($this->request, $this->subject->payload()->request());
    }

    public function testGetCreatedAt() : void {
        $createdAt = $this->subject->triggeredAt();

        $diff = $createdAt->diff(new \DateTimeImmutable());

        // Just make sure the datetime was created recently, i.e. within the last second.
        self::assertSame(0, $diff->s);
    }
}
