<?php declare(strict_types=1);

namespace Labrador\Test\Unit\TestHelper;

use Amp\Http\Server\Driver\Client;
use Amp\Http\Server\Middleware;
use Amp\Http\Server\Request;
use Amp\Http\Server\Response;
use Labrador\Test\Unit\Web\Stub\ResponseRequestHandlerStub;
use Labrador\Test\Unit\Web\Stub\SessionReadingRequestHandler;
use Labrador\Test\Unit\Web\Stub\SessionWritingRequestHandler;
use Labrador\TestHelper\RequestHandlerInvoker;
use Labrador\Web\HttpMethod;
use Labrador\Web\RequestAttribute;
use League\Uri\Http;
use Mockery;
use Mockery\MockInterface;
use PHPUnit\Framework\TestCase;

final class RequestHandlerInvokerTest extends TestCase {

    private Client&MockInterface $client;

    protected function setUp() : void {
        $this->client = Mockery::mock(Client::class);
    }

    public function testRequestHandlerInvokedWithCorrectInvokedRequestHandlerResponse() : void {
        $subject = RequestHandlerInvoker::withTestSessionMiddleware();

        $invokedRequestHandlerResponse = $subject->invokeRequestHandler(
            $request = new Request($this->client, HttpMethod::Get->value, Http::new('https://example.com')),
            $requestHandler = new ResponseRequestHandlerStub($response = new Response())
        );

        self::assertSame($requestHandler, $invokedRequestHandlerResponse->requestHandler());
        self::assertSame($request, $invokedRequestHandlerResponse->request());
        self::assertSame($response, $invokedRequestHandlerResponse->response());
    }

    public function testRequestHandlerInvokedWithInvokedRequestHandlerResponseWithCorrectSessionStorage() : void {
        $subject = RequestHandlerInvoker::withTestSessionMiddleware();

        $invokedRequestHandlerResponse = $subject->invokeRequestHandler(
            new Request($this->client, HttpMethod::Get->value, Http::new('https://example.com')),
            new SessionWritingRequestHandler(),
        );

        self::assertSame([
            'labrador.csrfToken' => 'known-token',
            'known-key' => 'known-value'
        ], $invokedRequestHandlerResponse->readSession());
    }

    public function testRequestHandlerInvokedWithApplicationMiddlewareAppliedToAllInvokedRequestHandlers() : void {
        $subject = RequestHandlerInvoker::withTestSessionMiddleware(
            [],
            $middleware = Mockery::mock(Middleware::class)
        );

        $middleware->shouldReceive('handleRequest')
            ->andReturn(new Response());

        $invokedRequestHandlerResponse = $subject->invokeRequestHandler(
            new Request($this->client, HttpMethod::Get->value, Http::new('https://example.com')),
            $requestHandler = new ResponseRequestHandlerStub(new Response())
        );

        self::assertSame($requestHandler, $invokedRequestHandlerResponse->requestHandler());
        self::assertCount(4, $invokedRequestHandlerResponse->middleware());
        self::assertSame($middleware, $invokedRequestHandlerResponse->middleware()[3]);

        $invokedRequestHandlerResponse = $subject->invokeRequestHandler(
            new Request($this->client, HttpMethod::Get->value, Http::new('https://example.com')),
            $requestHandler = new ResponseRequestHandlerStub(new Response())
        );

        self::assertSame($requestHandler, $invokedRequestHandlerResponse->requestHandler());
        self::assertCount(4, $invokedRequestHandlerResponse->middleware());
        self::assertSame($middleware, $invokedRequestHandlerResponse->middleware()[3]);
    }

    public function testRequestHandlerInvokedWithApplicationMiddlewareAndRequestHandlerSpecificMiddleware() : void {
        $subject = RequestHandlerInvoker::withTestSessionMiddleware(
            [],
            $middleware = Mockery::mock(Middleware::class)
        );

        $middleware->shouldReceive('handleRequest')->andReturn(new Response());

        $routeMiddleware = Mockery::mock(Middleware::class);
        $routeMiddleware->shouldReceive('handleRequest')->andReturn(new Response());

        $invokedRequestHandlerResponse = $subject->invokeRequestHandler(
            new Request($this->client, HttpMethod::Get->value, Http::new('https://example.com')),
            new ResponseRequestHandlerStub(new Response()),
            $routeMiddleware
        );

        $requestHandler = $invokedRequestHandlerResponse->requestHandler();

        self::assertInstanceOf(ResponseRequestHandlerStub::class, $requestHandler);
        self::assertCount(5, $invokedRequestHandlerResponse->middleware());
        self::assertSame($middleware, $invokedRequestHandlerResponse->middleware()[3]);
        self::assertSame($routeMiddleware, $invokedRequestHandlerResponse->middleware()[4]);

        $invokedRequestHandlerResponse = $subject->invokeRequestHandler(
            new Request($this->client, HttpMethod::Get->value, Http::new('https://example.com')),
            new ResponseRequestHandlerStub(new Response())
        );

        $requestHandler = $invokedRequestHandlerResponse->requestHandler();

        self::assertInstanceOf(ResponseRequestHandlerStub::class, $requestHandler);
        self::assertCount(4, $invokedRequestHandlerResponse->middleware());
        self::assertSame($middleware, $invokedRequestHandlerResponse->middleware()[3]);
    }

    public function testInitialSessionDataIsAvailableInInvokedRequestHandler() : void {
        $subject = RequestHandlerInvoker::withTestSessionMiddleware([
            'my-data-key' => 'some value'
        ]);
        $invokedRequestHandler = $subject->invokeRequestHandler(
            new Request($this->client, HttpMethod::Get->value, Http::new('http://example.com')),
            new SessionReadingRequestHandler('my-data-key')
        );

        self::assertSame('some value', $invokedRequestHandler->response()->getBody()->read());
    }

    public function testRequestHandlerAddedToRequestAttribute() : void {
        $subject = RequestHandlerInvoker::withTestSessionMiddleware();

        $subject->invokeRequestHandler(
            $request = new Request($this->client, HttpMethod::Get->value, Http::new('http://example.com')),
            $requestHandler = new ResponseRequestHandlerStub(new Response())
        );

        self::assertSame(
            $request->getAttribute(RequestAttribute::RequestHandler->value),
            $requestHandler
        );
    }
}
