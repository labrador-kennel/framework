<?php declare(strict_types=1);

namespace Labrador\Test\Unit\Web\Stub;

use Amp\Http\Server\Request;
use Amp\Http\Server\RequestHandler;
use Amp\Http\Server\Response;
use Amp\Http\Server\Session\Session;

class SessionWritingRequestHandler implements RequestHandler {

    public function handleRequest(Request $request) : Response {
        $request->getAttribute(Session::class)->set('known-key', 'known-value');
        return new Response(body: 'known-body');
    }
}
