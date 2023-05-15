<?php

namespace Labrador\DummyApp\Controller;

use Amp\Http\Server\Request;
use Amp\Http\Server\Response;
use Labrador\DummyApp\Middleware\ControllerSpecificMiddleware;
use Labrador\Web\Controller\Controller;
use Labrador\Web\Controller\HttpController;
use Labrador\Web\Router\GetMapping;

#[HttpController(
    requestMapping: new GetMapping('/hello/middleware'),
    middleware: [ControllerSpecificMiddleware::class]
)]
class HelloMiddlewareController implements Controller {

    public function toString() : string {
        return 'HelloMiddleware';
    }

    public function handleRequest(Request $request) : Response {
        $who = $request->getAttribute('labrador.http-dummy-app.routeMiddleware');
        return new Response(body: 'Hello, ' . $who . '!');
    }
}