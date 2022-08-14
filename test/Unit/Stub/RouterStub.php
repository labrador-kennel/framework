<?php

namespace Cspray\Labrador\Http\Test\Unit\Stub;

use Amp\Http\Server\Middleware;
use Amp\Http\Server\Request;
use Amp\Http\Server\Response;
use Amp\Http\Status;
use Cspray\Labrador\Http\Controller\Controller;
use Cspray\Labrador\Http\Router\Router;

class RouterStub implements Router {

    private ?Controller $controller = null;

    public function setController(Controller $controller) : void {
        $this->controller = $controller;
    }

    public function addRoute(string $method, string $pattern, Controller $controller, Middleware ...$middlewares) : void {
        throw new \RuntimeException('You should not add routes to the stubbed router');
    }

    public function match(Request $request) : Controller {
        return $this->controller ?? $this->getNotFoundController();
    }

    public function setNotFoundController(Controller $controller) : void {
        throw new \RuntimeException('You should not set a not found controller on the stubbed router');
    }

    public function getNotFoundController() : Controller {
        return new class implements Controller {

            public function toString() : string {
                return 'TestStubNotFoundController';
            }

            public function handleRequest(Request $request) : Response {
                return new Response(Status::NOT_FOUND, body: 'Test stub not found response');
            }
        };
    }

    public function setMethodNotAllowedController(Controller $controller) : void {
        throw new \RuntimeException('You should not set a method not allowed controller on the stubbed router');
    }

    public function getMethodNotAllowedController() : Controller {
        return new class implements Controller {

            public function toString() : string {
                return 'TestStubMethodNotAllowedController';
            }

            public function handleRequest(Request $request) : Response {
                return new Response(Status::METHOD_NOT_ALLOWED, body: 'Test stub method not allowed response');
            }
        };
    }

    public function getRoutes() : array {
        throw new \RuntimeException('You should not get routes from this router stub');
    }
}