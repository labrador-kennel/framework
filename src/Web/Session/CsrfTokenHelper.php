<?php declare(strict_types=1);

namespace Labrador\Web\Session;

use Amp\Http\Server\Request;
use Amp\Http\Server\Session\Session;
use Labrador\Web\Session\Exception\SessionHasNoCsrfToken;
use Labrador\Web\Session\Exception\SessionNotAttachedToRequest;

final class CsrfTokenHelper {

    public function token(Request $request) : string {
        if (!$request->hasAttribute(Session::class)) {
            throw SessionNotAttachedToRequest::fromSessionNotAttachedToRequest();
        }

        $session = $request->getAttribute(Session::class);
        assert($session instanceof Session);

        if (!$session->has('labrador.csrfToken')) {
            throw SessionHasNoCsrfToken::fromSessionDoesNotHaveCsrfToken();
        }

        return (string) $session->get('labrador.csrfToken');
    }

    public function isTokenValid(Request $request, string $token) : bool {
        return $this->token($request) === $token;
    }
}
