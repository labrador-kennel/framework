<?php declare(strict_types=1);


namespace Cspray\Labrador\Http\Test\Stub;

use Amp\Promise;
use Amp\Http\Server\Middleware;
use Amp\Http\Server\Request;
use Amp\Http\Server\Response;
use Amp\Http\Server\RequestHandler;
use function Amp\call;

class ResponseDecoratorMiddleware implements Middleware {

    /**
     * @param Request $request
     * @param RequestHandler $requestHandler
     *
     * @return Promise<\Amp\Http\Server\Response>
     */
    public function handleRequest(Request $request, RequestHandler $requestHandler): Promise {
        return call(function() use ($request, $requestHandler) {
            /** @var Response $response */
            $response = yield $requestHandler->handleRequest($request);
            $body = yield $response->getBody()->read();
            $decorated = $request->getAttribute('decorated');

            $response->setBody("$body $decorated");

            return $response;
        });
    }

}