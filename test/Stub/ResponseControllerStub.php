<?php declare(strict_types=1);


namespace Cspray\Labrador\Http\Test\Stub;

use Amp\Http\Server\Request;
use Amp\Http\Server\Response;
use Amp\Promise;
use Amp\Success;
use Cspray\Labrador\Http\Controller\Controller;

class ResponseControllerStub implements Controller {

    private $response;

    public function __construct(Response $response) {
        $this->response = $response;
    }

    public function toString(): string {
        return self::class;
    }

    /**
     * @param Request $request
     *
     * @return Promise<\Amp\Http\Server\Response>
     */
    public function handleRequest(Request $request): Promise {
        return new Success($this->response);
    }
}