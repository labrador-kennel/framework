<?php declare(strict_types=1);


namespace Labrador\Test\Unit\Web\Stub;

use Amp\Http\Server\Request;
use Amp\Http\Server\RequestHandler;
use Amp\Http\Server\Response;

class ResponseRequestHandlerStub implements RequestHandler {

    public function __construct(
        private readonly Response $response
    ) {
    }

    /**
     * @param Request $request
     *
     * @return Response
     */
    public function handleRequest(Request $request): Response {
        return $this->response;
    }
}
