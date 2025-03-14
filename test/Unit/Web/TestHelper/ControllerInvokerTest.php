<?php declare(strict_types=1);

namespace Labrador\Test\Unit\Web\TestHelper;

use Amp\Http\Server\Driver\Client;
use Amp\Http\Server\Middleware;
use Amp\Http\Server\Request;
use Amp\Http\Server\Response;
use Amp\Http\Server\Session\Session;
use Labrador\Test\Unit\Web\Stub\ResponseControllerStub;
use Labrador\Test\Unit\Web\Stub\SessionWritingControllerStub;
use Labrador\Web\Controller\MiddlewareController;
use Labrador\Web\HttpMethod;
use Labrador\Web\TestHelper\ControllerInvoker;
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

        $invokedController = $invokedControllerResponse->getInvokedController();

        self::assertInstanceOf(MiddlewareController::class, $invokedController);
        self::assertSame($controller, $invokedController->controller);
        self::assertSame($request, $invokedControllerResponse->getRequest());
        self::assertSame($response, $invokedControllerResponse->getResponse());
    }

    public function testControllerInvokedWithInvokedControllerResponseWithCorrectSessionStorage() : void {
        $subject = ControllerInvoker::withTestSessionMiddleware();

        $invokedControllerResponse = $subject->invokeController(
            new Request($this->client, HttpMethod::Get->value, Http::new('https://example.com')),
            new SessionWritingControllerStub(),
        );

        self::assertSame(['known-key' => 'known-value'], $invokedControllerResponse->readSession());
    }

    public function testControllerInvokedWithApplicationMiddlewareAppliedToAllInvokedControllers() : void {
        $subject = ControllerInvoker::withTestSessionMiddleware(
            $middleware = Mockery::mock(Middleware::class)
        );

        $middleware->shouldReceive('handleRequest')
            ->andReturn(new Response());

        $invokedControllerResponse = $subject->invokeController(
            new Request($this->client, HttpMethod::Get->value, Http::new('https://example.com')),
            new ResponseControllerStub(new Response())
        );

        $controller = $invokedControllerResponse->getInvokedController();

        self::assertInstanceOf(MiddlewareController::class, $controller);
        self::assertCount(3, $controller->middlewares);
        self::assertSame($middleware, $controller->middlewares[2]);

        $invokedControllerResponse = $subject->invokeController(
            new Request($this->client, HttpMethod::Get->value, Http::new('https://example.com')),
            new ResponseControllerStub(new Response())
        );

        $controller = $invokedControllerResponse->getInvokedController();

        self::assertInstanceOf(MiddlewareController::class, $controller);
        self::assertCount(3, $controller->middlewares);
        self::assertSame($middleware, $controller->middlewares[2]);
    }

    public function testControllerInvokedWithApplicationMiddlewareAndControllerSpecificMiddleware() : void {
        $subject = ControllerInvoker::withTestSessionMiddleware(
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

        $controller = $invokedControllerResponse->getInvokedController();

        self::assertInstanceOf(MiddlewareController::class, $controller);
        self::assertCount(4, $controller->middlewares);
        self::assertSame($middleware, $controller->middlewares[2]);
        self::assertSame($controllerMiddleware, $controller->middlewares[3]);

        $invokedControllerResponse = $subject->invokeController(
            new Request($this->client, HttpMethod::Get->value, Http::new('https://example.com')),
            new ResponseControllerStub(new Response())
        );

        $controller = $invokedControllerResponse->getInvokedController();

        self::assertInstanceOf(MiddlewareController::class, $controller);
        self::assertCount(3, $controller->middlewares);
        self::assertSame($middleware, $controller->middlewares[2]);
    }
}
