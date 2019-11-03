<?php declare(strict_types=1);

namespace Cspray\Labrador\Http\Test\Controller;

use Amp\Http\Server\Driver\Client;
use Amp\Http\Server\Request;
use Amp\Http\Server\Response;
use Amp\Http\Status;
use Amp\PHPUnit\AsyncTestCase;
use Amp\Promise;
use Cspray\Labrador\Http\Exception\InvalidTypeException;
use Cspray\Labrador\Http\Test\Stub\AfterActionResponseDecoratorHookableControllerStub;
use Cspray\Labrador\Http\Test\Stub\BeforeActionResponseHookableControllerStub;
use Cspray\Labrador\Http\Test\Stub\NonResponseReturningHandleHookableControllerStub;
use Cspray\Labrador\Http\Test\Stub\OnlyHandlerHookableControllerStub;
use Cspray\Labrador\Http\Test\Stub\SequenceHookableControllerStub;
use League\Uri\Http;

class HookableControllerTest extends AsyncTestCase {

    public function testHandleDoesNotReturnResponseInternalServerError() {
        $thrownException = null;
        try {
            $subject = new NonResponseReturningHandleHookableControllerStub();
            $client = $this->createMock(Client::class);
            $request = new Request($client, 'GET', Http::createFromString('/'));
            yield $subject->handleRequest($request);
        } catch (\Throwable $throwable) {
            $thrownException = $throwable;
        } finally {
            $this->assertInstanceOf(InvalidTypeException::class, $thrownException);
            $expectedMsg = 'The type resolved from a %s::handle() %s must be a %s.';
            $this->assertSame(sprintf(
                $expectedMsg,
                NonResponseReturningHandleHookableControllerStub::class,
                Promise::class,
                Response::class
            ), $thrownException->getMessage());
        }
    }

    public function testRequestReceivingCallsInvokedInOrder() {
        $subject = new SequenceHookableControllerStub();
        $client = $this->createMock(Client::class);
        $request = new Request($client, 'GET', Http::createFromString('/'));
        yield $subject->handleRequest($request);

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
        $response = yield $subject->handleRequest($request);
        $body = yield $response->getBody()->read();

        $this->assertSame(Status::OK, $response->getStatus());
        $this->assertSame('From beforeAction', $body);
    }

    public function testReturningResponseFromAfterActionOverridesHandle() {
        $subject = new AfterActionResponseDecoratorHookableControllerStub();
        $client = $this->createMock(Client::class);
        $request = new Request($client, 'GET', Http::createFromString('/'));
        $response = yield $subject->handleRequest($request);
        $body = yield $response->getBody()->read();

        $this->assertSame(Status::OK, $response->getStatus());
        $this->assertSame('A-OK', $body);
    }

    public function testReturnsResponseFromHandleIfNoBeforeOrAfterActionResponse() {
        $subject = new OnlyHandlerHookableControllerStub();
        $client = $this->createMock(Client::class);
        $request = new Request($client, 'GET', Http::createFromString('/'));
        $response = yield $subject->handleRequest($request);
        $this->assertInstanceOf(Response::class, $response);

        $body = yield $response->getBody()->read();

        $this->assertSame(Status::OK, $response->getStatus());
        $this->assertSame('From Only Handler', $body);
    }
}
