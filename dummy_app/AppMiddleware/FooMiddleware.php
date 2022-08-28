<?php

namespace Cspray\Labrador\HttpDummyApp\AppMiddleware;

use Amp\Http\Server\Middleware;
use Amp\Http\Server\Request;
use Amp\Http\Server\RequestHandler;
use Amp\Http\Server\Response;
use Cspray\Labrador\Http\Middleware\ApplicationMiddleware;
use Cspray\Labrador\Http\Middleware\Priority;
use Cspray\Labrador\HttpDummyApp\MiddlewareCallRegistry;

#[ApplicationMiddleware(priority: Priority::High)]
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