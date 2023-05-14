<?php declare(strict_types=1);

namespace Labrador\Web\Exception;

use Labrador\Web\Controller\Controller;
use Labrador\Web\Controller\SessionAccess;

final class SessionNotEnabled extends Exception {

    public static function fromSessionAccessRequired(Controller $controller, SessionAccess $sessionAccess) : self {
        return new self(sprintf(
            'The Controller "%s" requires %s Session access but Session support has not been enabled. Please ensure ' .
            'that you have configured a SessionFactory in your implemented ApplicationFeatures.',
            $controller->toString(),
            $sessionAccess->name
        ));
    }

}
