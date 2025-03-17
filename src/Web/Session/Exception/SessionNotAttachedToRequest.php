<?php declare(strict_types=1);

namespace Labrador\Web\Session\Exception;

use Labrador\Exception\Exception;

final class SessionNotAttachedToRequest extends Exception {

    public static function fromSessionNotAttachedToRequest() : self {
        return new self(
            'Attempted to access a session that has not been attached to the Request. Please ensure '
            . 'middleware that will attach Session to Request runs first.'
        );
    }
}
