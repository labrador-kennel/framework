<?php declare(strict_types=1);

namespace Labrador\Http\Application;

use Amp\Http\Server\Session\SessionMiddleware;
use Cspray\AnnotatedContainer\Attribute\Service;

#[Service]
class NoApplicationFeatures implements ApplicationFeatures {

    public function getSessionMiddleware() : ?SessionMiddleware {
        return null;
    }

    public function autoRedirectHttpToHttps() : bool {
        return false;
    }
}