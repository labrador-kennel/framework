<?php declare(strict_types=1);

namespace Labrador\Test\Helper;

use Amp\Http\Server\Session\SessionMiddleware;
use Labrador\Web\Application\ApplicationSettings;
use Labrador\Web\Application\StaticAssetSettings;

class StubApplicationSettings implements ApplicationSettings {

    public function getSessionMiddleware() : ?SessionMiddleware {
        return null;
    }

    public function getStaticAssetSettings() : ?StaticAssetSettings {
        return null;
    }
}