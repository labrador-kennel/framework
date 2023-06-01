<?php declare(strict_types=1);

namespace Labrador\Web\Security;

use Amp\Http\Server\Request;
use Amp\Http\Server\Session\Session;
use Cspray\AnnotatedContainer\Attribute\Service;
use Labrador\Security\TokenGenerator;
use Labrador\Web\Exception\SessionNotEnabled;

#[Service]
final class CsrfTokenManager {

    public function __construct(
        private readonly TokenGenerator $tokenGenerator,
    ) {}

    public function generateAndStore(Request $request) : string {
        if (!$request->hasAttribute(Session::class)) {
            throw SessionNotEnabled::fromCsrfTokenManagerRequiresSession();
        }

        $session = $request->getAttribute(Session::class);
        assert($session instanceof Session);

        $token = $this->tokenGenerator->generateToken();

        if ($session->has('csrfTokens')) {
            $tokens = json_decode($session->get('csrfTokens'), true);
        } else {
            $tokens = [];
        }

        $tokens[] = $token;
        $session->set('csrfTokens', json_encode($tokens));

        return $token;
    }

    public function validateAndExpire(Request $request, string $csrfToken) : bool {
        if (!$request->hasAttribute(Session::class)) {
            throw SessionNotEnabled::fromCsrfTokenManagerRequiresSession();
        }

        $session = $request->getAttribute(Session::class);
        assert($session instanceof Session);
        if (!$session->has('csrfTokens')) {
            return false;
        }

        $tokens = json_decode($session->get('csrfTokens'), true);
        $valid = false;
        foreach ($tokens as $index => $token) {
            if ($token === $csrfToken) {
                $valid = true;
                unset($tokens[$index]);
                break;
            }
        }
        $session->set('csrfTokens', json_encode($tokens));

        return $valid;
    }

}
