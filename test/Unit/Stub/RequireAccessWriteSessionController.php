<?php declare(strict_types=1);

namespace Labrador\Test\Unit\Stub;

use Amp\Http\Server\Request;
use Amp\Http\Server\Response;
use Amp\Http\Server\Session\Session;
use Labrador\Web\Controller\RequireSession;
use Labrador\Web\Controller\SelfDescribingController;
use Labrador\Web\Controller\SessionAccess;

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