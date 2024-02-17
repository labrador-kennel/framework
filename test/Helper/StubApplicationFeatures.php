<?php declare(strict_types=1);

namespace Labrador\Test\Helper;

use Amp\Http\Server\Session\SessionMiddleware;
use Labrador\Web\Application\ApplicationFeatures;
use Labrador\Web\Application\StaticAssetSettings;

class StubApplicationFeatures implements ApplicationFeatures {

    public function getSessionMiddleware() : ?SessionMiddleware {
        return null;
    }

    public function getStaticAssetSettings() : ?StaticAssetSettings {
        return null;
    }
}