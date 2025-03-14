<?php

namespace Labrador\DummyApp\Middleware;

use Amp\Http\Server\Middleware;
use Amp\Http\Server\Request;
use Amp\Http\Server\RequestHandler;
use Amp\Http\Server\Response;
use Cspray\AnnotatedContainer\Attribute\Service;
use Labrador\DummyApp\MiddlewareCallRegistry;

#[Service]
class FooMiddleware implements Middleware {

    public function __construct(
        private readonly MiddlewareCallRegistry $registry
    ) {}

    public function handleRequest(Request $request, RequestHandler $requestHandler) : Response {
        $this->registry->called($this);
        $request->setAttribute('labrador.http-dummy-app.middleware.foo', 'high');
        return $requestHandler->handleRequest($request);
    }
}