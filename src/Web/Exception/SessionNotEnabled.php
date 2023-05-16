<?php declare(strict_types=1);

namespace Labrador\Web\Exception;

use Labrador\Web\Middleware\OpenSession;

final class SessionNotEnabled extends Exception {

    public static function fromOpenSessionMiddlewareFoundNoSession() : self {
        return new self(sprintf(
            'The %s was added to a route but no session was found on the request.',
            OpenSession::class
        ));
    }

    public static function fromCsrfTokenManagerRequiresSession() : self {
        return new self('The CsrfTokenManager requires a session be enabled for a request.');
    }
}
