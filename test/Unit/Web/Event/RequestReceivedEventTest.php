<?php

namespace Labrador\Test\Unit\Web\Event;

use Amp\Http\Server\Driver\Client;
use Amp\Http\Server\Request;
use Labrador\Web\Application\ApplicationEvent;
use Labrador\Web\Application\Event\RequestReceived;
use League\Uri\Http;
use PHPUnit\Framework\TestCase;

class RequestReceivedEventTest extends TestCase {

    private Request $request;
    private RequestReceived $subject;

    protected function setUp() : void {
        parent::setUp();
        $this->request = new Request($this->getMockBuilder(Client::class)->getMock(), 'GET', Http::new('http://example.com'));
        $this->subject = new RequestReceived($this->request);
    }

    public function testGetName() : void {
        self::assertSame(ApplicationEvent::RequestReceived->value, $this->subject->name());
    }

    public function testGetTarget() : void {
        self::assertSame($this->request, $this->subject->payload());
    }

    public function testGetCreatedAt() : void {
        $createdAt = $this->subject->triggeredAt();

        $diff = $createdAt->diff(new \DateTimeImmutable());

        // Just make sure the datetime was created recently, i.e. within the last second.
        self::assertSame(0, $diff->s);
    }

}