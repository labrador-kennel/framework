<?php

namespace Labrador\DummyApp\Controller;

use Amp\Http\Server\Request;
use Amp\Http\Server\Response;
use Labrador\Web\Autowire\HttpController;
use Labrador\Web\Controller\Controller;
use Labrador\Web\Router\Mapping\GetMapping;

#[HttpController(new GetMapping('/hello/world'))]
class HelloWorldController implements Controller {

    public function handleRequest(Request $request) : Response {
        return new Response(body: 'Hello, world!');
    }

    public function toString() : string {
        return 'HelloWorld';
    }
}