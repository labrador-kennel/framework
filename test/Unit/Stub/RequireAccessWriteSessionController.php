<?php declare(strict_types=1);

namespace Labrador\Http\Test\Unit\Stub;

use Amp\Http\Server\Request;
use Amp\Http\Server\Response;
use Amp\Http\Server\Session\Session;
use Labrador\Http\Controller\RequireSession;
use Labrador\Http\Controller\SelfDescribingController;
use Labrador\Http\Controller\SessionAccess;

#[RequireSession(SessionAccess::Write)]
class RequireAccessWriteSessionController extends SelfDescribingController {

    public function handleRequest(Request $request) : Response {
        $session = $request->getAttribute(Session::class);
        assert($session instanceof Session);
        $val = $session->get('known-session-path');
        $session->set('known-session-path', 'prefixed_' . $val);

        return new Response(body: 'OK');
    }
}