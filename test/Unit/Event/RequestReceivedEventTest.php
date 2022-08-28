<?php

namespace Cspray\Labrador\Http\Test\Unit\Event;

use Amp\Http\Server\Driver\Client;
use Amp\Http\Server\Request;
use Cspray\Labrador\Http\ApplicationEvent;
use Cspray\Labrador\Http\Event\RequestReceivedEvent;
use League\Uri\Http;
use PHPUnit\Framework\TestCase;

class RequestReceivedEventTest extends TestCase {

    private Request $request;
    private RequestReceivedEvent $subject;

    protected function setUp() : void {
        parent::setUp();
        $this->request = new Request($this->getMockBuilder(Client::class)->getMock(), 'GET', Http::createFromString('http://example.com'));
        $this->subject = new RequestReceivedEvent($this->request);
    }

    public function testGetName() : void {
        self::assertSame(ApplicationEvent::RequestReceived->value, $this->subject->getName());
    }

    public function testGetTarget() : void {
        self::assertSame($this->request, $this->subject->getTarget());
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