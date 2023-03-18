<?php declare(strict_types=1);

namespace Labrador\Http\Test\Unit\Stub;

use Amp\Http\Server\Request;
use Amp\Http\Server\Response;
use Amp\Http\Server\Session\Session;
use Labrador\Http\Controller\SelfDescribingController;

class SessionGatheringController extends SelfDescribingController {

    private ?Session $session = null;

    public function handleRequest(Request $request) : Response {
        if ($request->hasAttribute(Session::class)) {
            $this->session = $request->getAttribute(Session::class);
        }

        return new Response(body: 'OK');
    }

    public function getSession() : ?Session {
        return $this->session;
    }
}