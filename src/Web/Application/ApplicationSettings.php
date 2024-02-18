<?php declare(strict_types=1);

namespace Labrador\Web\Application;

use Amp\Http\Server\Session\SessionMiddleware;
use Cspray\AnnotatedContainer\Attribute\Service;

#[Service]
interface ApplicationSettings {

    public function getSessionMiddleware() : ?SessionMiddleware;

    public function getStaticAssetSettings() : ?StaticAssetSettings;

}