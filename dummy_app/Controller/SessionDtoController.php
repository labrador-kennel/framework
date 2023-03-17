<?php declare(strict_types=1);

namespace Labrador\HttpDummyApp\Controller;

use Amp\Http\Server\Request;
use Amp\Http\Server\Response;
use Amp\Http\Server\Session\Session;
use Labrador\Http\Controller\ControllerActions;
use Labrador\Http\Controller\Get;
use Labrador\Http\Controller\RequireSession;
use Labrador\Http\Controller\SessionAccess;

#[ControllerActions, RequireSession(SessionAccess::Write)]
class SessionDtoController {

    #[Get('/dto/controller-session/write')]
    public function write(Request $request) : Response {
        if ($request->hasAttribute(Session::class)) {
            $session = $request->getAttribute(Session::class);
            $session->set('known-session-path', __METHOD__);
            return new Response(body: 'OK');
        }

        return new Response(body: 'No Session Found');
    }

    #[Get('/dto/controller-session/read')]
    public function read(Request $request) : Response {
        $body = $request->getAttribute(Session::class)->get('known-session-path');
        return new Response(body: $body);
    }

}
