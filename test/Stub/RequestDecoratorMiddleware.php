<?php declare(strict_types=1);

namespace Cspray\Labrador\Http\Test\Stub;

use Amp\Promise;
use Amp\Http\Server\Middleware;
use Amp\Http\Server\Request;
use Amp\Http\Server\RequestHandler;

class RequestDecoratorMiddleware implements Middleware {

    /**
     * @param Request $request
     * @param RequestHandler $requestHandler
     *
     * @return Promise<\Amp\Http\Server\Response>
     */
    public function handleRequest(Request $request, RequestHandler $requestHandler): Promise {
        $request->setAttribute('decorated', 'foobar');
        return $requestHandler->handleRequest($request);
    }

}