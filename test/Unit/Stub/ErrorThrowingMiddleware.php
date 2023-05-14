<?php declare(strict_types=1);

namespace Labrador\Test\Unit\Stub;

use Amp\Http\Server\Middleware;
use Amp\Http\Server\Request;
use Amp\Http\Server\RequestHandler;
use Amp\Http\Server\Response;
use Throwable;

final class ErrorThrowingMiddleware implements Middleware {

    public function __construct(
        private readonly Throwable $throwable
    ) {}

    public function handleRequest(Request $request, RequestHandler $requestHandler) : Response {
        throw $this->throwable;
    }
}
