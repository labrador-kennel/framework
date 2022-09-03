<?php

namespace Labrador\HttpDummyApp\Controller;

use Amp\Http\Server\Request;
use Amp\Http\Server\Response;
use Labrador\Http\Controller\Controller;
use Labrador\Http\Controller\HttpController;
use Labrador\Http\HttpMethod;
use Labrador\HttpDummyApp\Middleware\ControllerSpecificMiddleware;

#[HttpController(
    method: HttpMethod::Get,
    pattern: '/hello/middleware',
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