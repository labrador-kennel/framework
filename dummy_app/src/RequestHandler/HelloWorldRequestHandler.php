<?php

namespace Labrador\DummyApp\RequestHandler;

use Amp\Http\Server\Request;
use Amp\Http\Server\RequestHandler;
use Amp\Http\Server\Response;

class HelloWorldRequestHandler implements RequestHandler {

    public function handleRequest(Request $request) : Response {
        return new Response(body: 'Hello, world!');
    }

}