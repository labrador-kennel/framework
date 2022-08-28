<?php

namespace Cspray\Labrador\Http\Test\Unit\Event;

use Amp\Http\Server\Response;
use Cspray\Labrador\Http\ApplicationEvent;
use Cspray\Labrador\Http\Event\ResponseSentEvent;
use Cspray\Labrador\Http\RequestAttribute;
use PHPUnit\Framework\TestCase;
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;

class ResponseSentEventTest extends TestCase {

    private Response $response;
    private ResponseSentEvent $subject;
    private UuidInterface $uuid;

    protected function setUp() : void {
        parent::setUp();
        $this->response = new Response();
        $this->uuid = Uuid::uuid6();
        $this->subject = new ResponseSentEvent($this->response, $this->uuid);
    }

    public function testGetName() : void {
        self::assertSame(ApplicationEvent::ResponseSent->value, $this->subject->getName());
    }

    public function testGetTarget() : void {
        self::assertSame($this->response, $this->subject->getTarget());
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