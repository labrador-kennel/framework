<?php declare(strict_types=1);

namespace Labrador\Test\Unit\Web\Stub;

use Amp\Http\Server\Request;
use Amp\Http\Server\RequestHandler;
use Amp\Http\Server\Response;
use Throwable;

final class ErrorThrowingRequestHandler implements RequestHandler {

    public function __construct(
        private readonly Throwable $throwable
    ) {
    }

    public function handleRequest(Request $request) : Response {
        throw $this->throwable;
    }
}
