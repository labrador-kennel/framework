<?php

namespace Labrador\Test\Unit\Web\Event;

use Amp\Http\Server\HttpServer;
use Labrador\Web\Application\ApplicationEvent;
use Labrador\Web\Application\Event\ReceivingConnections;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class ReceivingConnectionsEventTest extends TestCase {

    private HttpServer&MockObject $server;
    private ReceivingConnections $subject;

    protected function setUp() : void {
        parent::setUp();
        $this->server = $this->getMockBuilder(HttpServer::class)->getMock();
        $this->subject = new ReceivingConnections($this->server);
    }

    public function testGetName() : void {
        self::assertSame(ApplicationEvent::ReceivingConnections->value, $this->subject->getName());
    }

    public function testGetTarget() : void {
        self::assertSame($this->server, $this->subject->getTarget());
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