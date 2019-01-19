<?php declare(strict_types=1);

namespace Cspray\Labrador\Http\Test\Stub;

use Amp\Failure;
use Amp\Http\Server\Request;
use Amp\Http\Server\Response;
use Amp\Promise;
use Amp\Success;
use Cspray\Labrador\Http\Controller\Controller;

class ErrorThrowingController implements Controller {

    public function toString(): string {
        return self::class;
    }

    /**
     * @param Request $request
     *
     * @return Promise<\Amp\Http\Server\Response>
     */
    public function handleRequest(Request $request): Promise {
        return new Failure(new \Exception('Controller thrown exception'));
    }
}