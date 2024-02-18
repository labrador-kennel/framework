<?php declare(strict_types=1);

namespace Labrador\DummyApp;

use Amp\Http\Server\Session\LocalSessionStorage;
use Amp\Http\Server\Session\SessionFactory;
use Amp\Http\Server\Session\SessionMiddleware;
use Amp\Sync\LocalKeyedMutex;
use Cspray\AnnotatedContainer\Attribute\Service;
use Labrador\Web\Application\ApplicationSettings;
use Labrador\Web\Application\StaticAssetSettings;

#[Service]
final class DummyApplicationSettings implements ApplicationSettings {

    public function getSessionMiddleware() : ?SessionMiddleware {
        return new SessionMiddleware(
            new SessionFactory(
                new LocalKeyedMutex(),
                new LocalSessionStorage()
            )
        );
    }

    public function getStaticAssetSettings() : ?StaticAssetSettings {
        return null;
    }

}