<?php declare(strict_types=1);

namespace Labrador\Test\Unit\TestHelper;

use Amp\Http\Server\Driver\Client;
use Amp\Http\Server\Middleware;
use Amp\Http\Server\Request;
use Amp\Http\Server\Response;
use Labrador\Test\Unit\Web\Stub\ResponseControllerStub;
use Labrador\Test\Unit\Web\Stub\SessionReadingControllerStub;
use Labrador\Test\Unit\Web\Stub\SessionWritingControllerStub;
use Labrador\TestHelper\ControllerInvoker;
use Labrador\Web\Controller\MiddlewareController;
use Labrador\Web\HttpMethod;
use League\Uri\Http;
use Mockery;
use Mockery\MockInterface;
use PHPUnit\Framework\TestCase;

final class ControllerInvokerTest extends TestCase {

    private Client&MockInterface $client;

    protected function setUp() : void {
        $this->client = Mockery::mock(Client::class);
    }

    public function testControllerInvokedWithCorrectInvokedControllerResponse() : void {
        $subject = ControllerInvoker::withTestSessionMiddleware();

        $invokedControllerResponse = $subject->invokeController(
            $request = new Request($this->client, HttpMethod::Get->value, Http::new('https://example.com')),
            $controller = new ResponseControllerStub($response = new Response())
        );

        $invokedController = $invokedControllerResponse->invokedController();

        self::assertInstanceOf(MiddlewareController::class, $invokedController);
        self::assertSame($controller, $invokedController->controller);
        self::assertSame($request, $invokedControllerResponse->request());
        self::assertSame($response, $invokedControllerResponse->response());
    }

    public function testControllerInvokedWithInvokedControllerResponseWithCorrectSessionStorage() : void {
        $subject = ControllerInvoker::withTestSessionMiddleware();

        $invokedControllerResponse = $subject->invokeController(
            new Request($this->client, HttpMethod::Get->value, Http::new('https://example.com')),
            new SessionWritingControllerStub(),
        );

        self::assertSame([
            'labrador.csrfToken' => 'known-token',
            'known-key' => 'known-value'
        ], $invokedControllerResponse->readSession());
    }

    public function testControllerInvokedWithApplicationMiddlewareAppliedToAllInvokedControllers() : void {
        $subject = ControllerInvoker::withTestSessionMiddleware(
            [],
            $middleware = Mockery::mock(Middleware::class)
        );

        $middleware->shouldReceive('handleRequest')
            ->andReturn(new Response());

        $invokedControllerResponse = $subject->invokeController(
            new Request($this->client, HttpMethod::Get->value, Http::new('https://example.com')),
            new ResponseControllerStub(new Response())
        );

        $controller = $invokedControllerResponse->invokedController();

        self::assertInstanceOf(MiddlewareController::class, $controller);
        self::assertCount(4, $controller->middlewares);
        self::assertSame($middleware, $controller->middlewares[3]);

        $invokedControllerResponse = $subject->invokeController(
            new Request($this->client, HttpMethod::Get->value, Http::new('https://example.com')),
            new ResponseControllerStub(new Response())
        );

        $controller = $invokedControllerResponse->invokedController();

        self::assertInstanceOf(MiddlewareController::class, $controller);
        self::assertCount(4, $controller->middlewares);
        self::assertSame($middleware, $controller->middlewares[3]);
    }

    public function testControllerInvokedWithApplicationMiddlewareAndControllerSpecificMiddleware() : void {
        $subject = ControllerInvoker::withTestSessionMiddleware(
            [],
            $middleware = Mockery::mock(Middleware::class)
        );

        $middleware->shouldReceive('handleRequest')->andReturn(new Response());

        $controllerMiddleware = Mockery::mock(Middleware::class);
        $controllerMiddleware->shouldReceive('handleRequest')->andReturn(new Response());

        $invokedControllerResponse = $subject->invokeController(
            new Request($this->client, HttpMethod::Get->value, Http::new('https://example.com')),
            new ResponseControllerStub(new Response()),
            $controllerMiddleware
        );

        $controller = $invokedControllerResponse->invokedController();

        self::assertInstanceOf(MiddlewareController::class, $controller);
        self::assertCount(5, $controller->middlewares);
        self::assertSame($middleware, $controller->middlewares[3]);
        self::assertSame($controllerMiddleware, $controller->middlewares[4]);

        $invokedControllerResponse = $subject->invokeController(
            new Request($this->client, HttpMethod::Get->value, Http::new('https://example.com')),
            new ResponseControllerStub(new Response())
        );

        $controller = $invokedControllerResponse->invokedController();

        self::assertInstanceOf(MiddlewareController::class, $controller);
        self::assertCount(4, $controller->middlewares);
        self::assertSame($middleware, $controller->middlewares[3]);
    }

    public function testInitialSessionDataIsAvailableInInvokedController() : void {
        $subject = ControllerInvoker::withTestSessionMiddleware([
            'my-data-key' => 'some value'
        ]);
        $invokedController = $subject->invokeController(
            new Request($this->client, HttpMethod::Get->value, Http::new('http://example.com')),
            new SessionReadingControllerStub('my-data-key')
        );

        self::assertSame('some value', $invokedController->response()->getBody()->read());
    }
}
