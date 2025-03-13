<?php

namespace Labrador\Test\Unit\Web\Event;

use Amp\Http\Server\Driver\Client;
use Amp\Http\Server\Request;
use Amp\Http\Server\Response;
use Labrador\Web\Application\ApplicationEvent;
use Labrador\Web\Application\Event\ResponseSent;
use Labrador\Web\RequestAttribute;
use League\Uri\Http;
use PHPUnit\Framework\TestCase;
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;

class ResponseSentEventTest extends TestCase {

    private Request $request;
    private Response $response;
    private ResponseSent $subject;

    protected function setUp() : void {
        parent::setUp();
        $this->request = new Request(
            $this->getMockBuilder(Client::class)->getMock(),
            'GET',
            Http::new('http://example.com')
        );
        $this->response = new Response();
        $this->subject = new ResponseSent($this->request, $this->response);
    }

    public function testGetName() : void {
        self::assertSame(ApplicationEvent::ResponseSent->value, $this->subject->name());
    }

    public function testGetTarget() : void {
        self::assertSame($this->request, $this->subject->payload()->request());
        self::assertSame($this->response, $this->subject->payload()->response());
    }

    public function testGetCreatedAt() : void {
        $createdAt = $this->subject->triggeredAt();

        $diff = $createdAt->diff(new \DateTimeImmutable());

        // Just make sure the datetime was created recently, i.e. within the last second.
        self::assertSame(0, $diff->s);
    }

}