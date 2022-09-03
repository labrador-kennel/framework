<?php

namespace Labrador\HttpDummyApp\Controller;

use Amp\Http\Server\Request;
use Amp\Http\Server\Response;
use Labrador\Http\Controller\Controller;
use Labrador\Http\Controller\HttpController;
use Labrador\Http\HttpMethod;

#[HttpController(HttpMethod::Get, '/hello/world')]
class HelloWorldController implements Controller {

    public function handleRequest(Request $request) : Response {
        return new Response(body: 'Hello, world!');
    }

    public function toString() : string {
        return 'HelloWorld';
    }
}