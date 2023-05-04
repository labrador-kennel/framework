<?php declare(strict_types=1);

namespace Labrador\HttpDummyApp;

use Amp\Http\Server\Session\LocalSessionStorage;
use Amp\Http\Server\Session\SessionFactory;
use Amp\Http\Server\Session\SessionMiddleware;
use Amp\Sync\LocalKeyedMutex;
use Cspray\AnnotatedContainer\Attribute\Service;
use Labrador\Http\Application\ApplicationFeatures;

#[Service(primary: true)]
final class DummyApplicationFeatures implements ApplicationFeatures {

    public function getSessionMiddleware() : ?SessionMiddleware {
        return new SessionMiddleware(
            new SessionFactory(
                new LocalKeyedMutex(),
                new LocalSessionStorage()
            )
        );
    }

    public function autoRedirectHttpToHttps() : bool {
        return false;
    }
}