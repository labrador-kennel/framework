<?php declare(strict_types=1);

namespace Labrador\Test\Unit\Web\Stub;

use Amp\Http\Server\Request;
use Amp\Http\Server\Response;
use Amp\Http\Server\Session\Session;
use Labrador\Web\Controller\ControllerActions;
use Labrador\Web\Controller\Get;
use Labrador\Web\Controller\RequireSession;
use Labrador\Web\Controller\SessionAccess;

#[ControllerActions, RequireSession(SessionAccess::Write)]
class RequireSessionOnEveryControllerAction {

    #[Get('/login')]
    public function login(Request $request) : Response {
        $request->getAttribute(Session::class)->set('known-session-path', 'login');
        return new Response(body: 'OK');
    }

}
