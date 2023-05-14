<?php declare(strict_types=1);

namespace Labrador\DummyApp\Controller;

use Amp\Http\Server\Request;
use Amp\Http\Server\Response;
use Amp\Http\Server\Session\Session;
use Labrador\Web\Controller\ControllerActions;
use Labrador\Web\Controller\Get;
use Labrador\Web\Controller\RequireSession;
use Labrador\Web\Controller\SessionAccess;

#[ControllerActions]
final class SessionActionDtoController {

    #[Get('/dto/action-session/write'), RequireSession(SessionAccess::Write)]
    public function write(Request $request) : Response {
        $request->getAttribute(Session::class)->set('known-session-path', 'Known Session Value');
        return new Response(body: 'OK');
    }

    #[Get('/dto/action-session/read'), RequireSession(SessionAccess::Read)]
    public function read(Request $request) : Response {
        $body = $request->getAttribute(Session::class)->get('known-session-path');
        return new Response(body: $body);
    }

}