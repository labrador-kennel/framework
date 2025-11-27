<?php declare(strict_types=1);

namespace Labrador\Test\Unit\Web\Stub;

use Amp\Http\Server\Request;
use Amp\Http\Server\RequestHandler;
use Amp\Http\Server\Response;
use Amp\Http\Server\Session\Session;
use PHPUnit\Framework\Assert;

class SessionDestroyingRequestHandler implements RequestHandler {

    public function handleRequest(Request $request) : Response {
        $session = $request->getAttribute(Session::class);

        Assert::assertInstanceOf(Session::class, $session);

        $session->destroy();

        return new Response(body: 'Session destroyed');
    }
}
