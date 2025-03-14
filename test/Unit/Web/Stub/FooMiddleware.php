<?php declare(strict_types=1);

namespace Labrador\Test\Unit\Web\Stub;

use Amp\Http\Server\Middleware;
use Amp\Http\Server\Request;
use Amp\Http\Server\RequestHandler;
use Amp\Http\Server\Response;

class FooMiddleware implements Middleware {

    public function handleRequest(Request $request, RequestHandler $requestHandler) : Response {
        return $requestHandler->handleRequest($request);
    }
}
