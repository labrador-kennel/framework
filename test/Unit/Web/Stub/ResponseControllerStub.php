<?php declare(strict_types=1);


namespace Labrador\Test\Unit\Web\Stub;

use Amp\Http\Server\Request;
use Amp\Http\Server\Response;
use Amp\Promise;
use Amp\Success;
use Labrador\Web\Controller\Controller;

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
    public function handleRequest(Request $request): Response {
        return $this->response;
    }
}
