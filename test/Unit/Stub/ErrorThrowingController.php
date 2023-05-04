<?php declare(strict_types=1);

namespace Labrador\Http\Test\Unit\Stub;

use Amp\Http\Server\Request;
use Amp\Http\Server\Response;
use Labrador\Http\Controller\Controller;
use Throwable;

class ErrorThrowingController implements Controller {

    public function __construct(
        private readonly Throwable $throwable
    ) {}

    public function toString(): string {
        return self::class;
    }

    public function handleRequest(Request $request) : Response {
        throw $this->throwable;
    }
}
