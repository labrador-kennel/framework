<?php declare(strict_types=1);

namespace Labrador\Test\Unit\Web\Controller;

use Amp\Http\Server\Driver\Client;
use Amp\Http\Server\Request;
use Amp\Http\Server\Response;
use Amp\Http\HttpStatus;
use Amp\PHPUnit\AsyncTestCase;
use Labrador\Test\Unit\Web\Stub\AfterActionResponseDecoratorHookableControllerStub;
use Labrador\Test\Unit\Web\Stub\BeforeActionResponseHookableControllerStub;
use Labrador\Test\Unit\Web\Stub\OnlyHandlerHookableControllerStub;
use Labrador\Test\Unit\Web\Stub\SequenceHookableControllerStub;
use League\Uri\Http;
use PHPUnit\Framework\TestCase;

class HookableControllerTest extends TestCase {

    public function testRequestReceivingCallsInvokedInOrder() {
        $subject = new SequenceHookableControllerStub();
        $client = $this->createMock(Client::class);
        $request = new Request($client, 'GET', Http::createFromString('/'));
        $subject->handleRequest($request);

        $expected = [
            ['beforeAction', $request],
            ['handle', $request],
            ['afterAction', $request],
        ];
        $this->assertSame($expected, $subject->getReceivedRequests());
    }

    public function testReturningResponseFromBeforeActionShortCircuitsHandle() {
        $subject = new BeforeActionResponseHookableControllerStub();
        $client = $this->createMock(Client::class);
        $request = new Request($client, 'GET', Http::createFromString('/'));
        $response = $subject->handleRequest($request);
        $body = $response->getBody()->read();

        $this->assertSame(HttpStatus::OK, $response->getStatus());
        $this->assertSame('From beforeAction', $body);
    }

    public function testReturningResponseFromAfterActionOverridesHandle() {
        $subject = new AfterActionResponseDecoratorHookableControllerStub();
        $client = $this->createMock(Client::class);
        $request = new Request($client, 'GET', Http::createFromString('/'));
        $response = $subject->handleRequest($request);
        $body = $response->getBody()->read();

        $this->assertSame(HttpStatus::OK, $response->getStatus());
        $this->assertSame('A-OK', $body);
    }

    public function testReturnsResponseFromHandleIfNoBeforeOrAfterActionResponse() {
        $subject = new OnlyHandlerHookableControllerStub();
        $client = $this->createMock(Client::class);
        $request = new Request($client, 'GET', Http::createFromString('/'));
        $response = $subject->handleRequest($request);
        $this->assertInstanceOf(Response::class, $response);

        $body = $response->getBody()->read();

        $this->assertSame(HttpStatus::OK, $response->getStatus());
        $this->assertSame('From Only Handler', $body);
    }
}
