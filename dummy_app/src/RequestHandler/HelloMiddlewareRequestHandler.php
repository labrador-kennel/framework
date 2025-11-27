<?php

namespace Labrador\DummyApp\RequestHandler;

use Amp\Http\Server\Request;
use Amp\Http\Server\RequestHandler;
use Amp\Http\Server\Response;

class HelloMiddlewareRequestHandler implements RequestHandler {

    public function handleRequest(Request $request) : Response {
        $who = $request->getAttribute('labrador.http-dummy-app.routeMiddleware');
        return new Response(body: 'Hello, ' . $who . '!');
    }
}