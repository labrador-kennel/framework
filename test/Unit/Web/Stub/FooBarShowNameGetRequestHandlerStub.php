<?php declare(strict_types=1);

namespace Labrador\Test\Unit\Web\Stub;

use Amp\Http\Server\Request;
use Amp\Http\Server\RequestHandler;
use Amp\Http\Server\Response;

final class FooBarShowNameGetRequestHandlerStub implements RequestHandler {

    public function handleRequest(Request $request) : Response {
        return new Response();
    }
}
