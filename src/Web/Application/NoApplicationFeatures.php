<?php declare(strict_types=1);

namespace Labrador\Web\Application;

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

    public function getStaticAssetSettings() : ?StaticAssetSettings {
        return null;
    }

    public function getHttpsRedirectPort() : ?int {
        return null;
    }
}