<?php declare(strict_types=1);

namespace Cspray\Labrador\Http\Test\Unit\Stub;

use Amp\Http\Server\Middleware;
use Amp\Http\Server\Request;
use Amp\Http\Server\RequestHandler;
use Amp\Http\Server\Response;
use Cspray\Labrador\Http\Test\Stub\Promise;

class RequestDecoratorMiddleware implements Middleware {

    /**
     * @param Request $request
     * @param RequestHandler $requestHandler
     *
     * @return Promise<\Amp\Http\Server\Response>
     */
    public function handleRequest(Request $request, RequestHandler $requestHandler): Response {
        $request->setAttribute('decorated', 'foobar');
        return $requestHandler->handleRequest($request);
    }
}