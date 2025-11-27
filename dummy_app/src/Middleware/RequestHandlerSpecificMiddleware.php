<?php

namespace Labrador\DummyApp\Middleware;

use Amp\Http\Server\Middleware;
use Amp\Http\Server\Request;
use Amp\Http\Server\RequestHandler;
use Amp\Http\Server\Response;
use Cspray\AnnotatedContainer\Attribute\Service;

#[Service]
class RequestHandlerSpecificMiddleware implements Middleware {

    public function handleRequest(Request $request, RequestHandler $requestHandler) : Response {
        $request->setAttribute('labrador.http-dummy-app.routeMiddleware', 'Universe');
        return $requestHandler->handleRequest($request);
    }
}