<?php declare(strict_types=1);


namespace Labrador\Test\Unit\Web\Stub;

use Amp\Http\Server\Middleware;
use Amp\Http\Server\Request;
use Amp\Http\Server\RequestHandler;
use Amp\Http\Server\Response;
use Labrador\Test\Stub\Promise;

class ResponseDecoratorMiddleware implements Middleware {

    /**
     * @param Request $request
     * @param RequestHandler $requestHandler
     *
     * @return Promise<\Amp\Http\Server\Response>
     */
    public function handleRequest(Request $request, RequestHandler $requestHandler): Response {
        /** @var Response $response */
        $response = $requestHandler->handleRequest($request);
        $body = $response->getBody()->read();
        $decorated = $request->getAttribute('decorated');

        $response->setBody("$body $decorated");

        return $response;
    }
}
