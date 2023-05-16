<?php declare(strict_types=1);

namespace Labrador\Test\Unit\Web\Stub;

use Amp\Http\Server\Request;
use Amp\Http\Server\Response;
use Amp\Http\Server\Session\Session;
use Labrador\Web\Controller\RequireSession;
use Labrador\Web\Controller\SelfDescribingController;
use Labrador\Web\Controller\SessionAccess;

#[RequireSession(SessionAccess::Read)]
class RequireAccessReadSessionController extends SelfDescribingController {

    private ?string $sessionValue = null;

    public function handleRequest(Request $request) : Response {
        $this->sessionValue = $request->getAttribute(Session::class)->get('known-session-path');
        return new Response(body: 'OK');
    }

    public function getSessionValue() : ?string {
        return $this->sessionValue;
    }

}
