<?php declare(strict_types=1);

namespace Labrador\Http\Test\Unit\Stub;

use Amp\Failure;
use Amp\Http\Server\Request;
use Amp\Promise;
use Amp\Success;
use Labrador\Http\Controller\Controller;

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
