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
        self::assertSame(ApplicationEvent::ReceivingConnections->value, $this->subject->name());
    }

    public function testGetTarget() : void {
        self::assertSame($this->server, $this->subject->payload());
    }

    public function testGetCreatedAt() : void {
        $createdAt = $this->subject->triggeredAt();

        $diff = $createdAt->diff(new \DateTimeImmutable());

        // Just make sure the datetime was created recently, i.e. within the last second.
        self::assertSame(0, $diff->s);
    }

}