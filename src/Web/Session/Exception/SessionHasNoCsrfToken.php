<?php declare(strict_types=1);

namespace Labrador\Web\Session\Exception;

use Labrador\Exception\Exception;

final class SessionHasNoCsrfToken extends Exception {

    public static function fromSessionDoesNotHaveCsrfToken() : self {
        return new self('Attached session has no CSRF token associated with it.');
    }

}