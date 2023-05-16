<?php declare(strict_types=1);

namespace Labrador\Web\Exception;

use Labrador\Web\Controller\Controller;
use Labrador\Web\Controller\SessionAccess;
use Labrador\Web\Middleware\OpenSessionMiddleware;

final class SessionNotEnabled extends Exception {

    public static function fromOpenSessionMiddlewareFoundNoSession() : self {
        return new self(sprintf(
            'The %s was added to a route but no session was found on the request.',
            OpenSessionMiddleware::class
        ));
    }
}
