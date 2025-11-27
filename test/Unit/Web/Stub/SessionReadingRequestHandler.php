<?php declare(strict_types=1);

namespace Labrador\Test\Unit\Web\Stub;

use Amp\Http\Server\Request;
use Amp\Http\Server\RequestHandler;
use Amp\Http\Server\Response;
use Amp\Http\Server\Session\Session;

final class SessionReadingRequestHandler implements RequestHandler {

    public function __construct(
        private readonly string $sessionDataKey
    ) {
    }

    public function handleRequest(Request $request) : Response {
        $session = $request->getAttribute(Session::class);
        assert($session instanceof Session);

        return new Response(body: $session->get($this->sessionDataKey) ?? '');
    }
}
