<?php

namespace Labrador\DummyApp\Middleware;

use Amp\Http\Server\Middleware;
use Amp\Http\Server\Request;
use Amp\Http\Server\RequestHandler;
use Amp\Http\Server\Response;
use Labrador\DummyApp\MiddlewareCallRegistry;
use Labrador\Web\Middleware\ApplicationMiddleware;
use Labrador\Web\Middleware\Priority;

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