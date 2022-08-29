<?php

namespace Cspray\Labrador\HttpDummyApp\Middleware;

use Amp\Http\Server\Middleware;
use Amp\Http\Server\Request;
use Amp\Http\Server\RequestHandler;
use Amp\Http\Server\Response;
use Cspray\Labrador\Http\Middleware\ApplicationMiddleware;
use Cspray\Labrador\Http\Middleware\Priority;
use Cspray\Labrador\HttpDummyApp\MiddlewareCallRegistry;

#[ApplicationMiddleware(priority: Priority::Medium)]
class BazMiddleware implements Middleware {

    public function __construct(
        private readonly MiddlewareCallRegistry $registry
    ) {}

    public function handleRequest(Request $request, RequestHandler $requestHandler) : Response {
        $this->registry->called($this);
        $request->setAttribute('labrador.http-dummy-app.middleware.baz', 'medium');
        return $requestHandler->handleRequest($request);
    }
}