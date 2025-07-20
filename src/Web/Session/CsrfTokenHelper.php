<?php declare(strict_types=1);

namespace Labrador\Web\Session;

use Amp\Http\Server\Request;
use Cspray\AnnotatedContainer\Attribute\Service;
use Labrador\Web\Session\Exception\SessionHasNoCsrfToken;

#[Service]
final readonly class CsrfTokenHelper {

    public function __construct(
        private SessionHelper $sessionHelper
    ) {
    }

    public function token(Request $request) : string {
        $csrfTokenAttribute = new CsrfTokenAttribute();

        if (!$this->sessionHelper->has($request, $csrfTokenAttribute)) {
            throw SessionHasNoCsrfToken::fromSessionDoesNotHaveCsrfToken();
        }

        return (string) $this->sessionHelper->get($request, $csrfTokenAttribute);
    }

    public function isTokenValid(Request $request, string $token) : bool {
        return $this->token($request) === $token;
    }
}
