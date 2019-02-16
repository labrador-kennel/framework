<?php declare(strict_types=1);

namespace Cspray\Labrador\Http\Test\Stub;

use Amp\Http\Server\Request;
use Amp\Http\Server\Response;
use Amp\Promise;
use Amp\Success;
use Cspray\Labrador\Http\Controller\Controller;

class ToStringControllerStub implements Controller {

    private $string;

    public function __construct(string $string) {
        $this->string = $string;
    }

    /**
     * @param Request $request
     *
     * @return Promise<\Amp\Http\Server\Response>
     */
    public function handleRequest(Request $request): Promise {
        return new Success(new Response());
    }

    public function toString(): string {
        return $this->string;
    }
}
