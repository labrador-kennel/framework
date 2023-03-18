<?php declare(strict_types=1);

namespace Labrador\Http\Test\Unit\Stub;

use Amp\Http\Server\Request;
use Amp\Http\Server\Response;
use Amp\Http\Server\Session\Session;
use Labrador\Http\Controller\ControllerActions;
use Labrador\Http\Controller\Get;
use Labrador\Http\Controller\RequireSession;
use Labrador\Http\Controller\SessionAccess;

#[ControllerActions, RequireSession(SessionAccess::Write)]
class RequireSessionOnEveryControllerAction {

    #[Get('/login')]
    public function login(Request $request) : Response {
        $request->getAttribute(Session::class)->set('known-session-path', 'login');
        return new Response(body: 'OK');
    }

}
