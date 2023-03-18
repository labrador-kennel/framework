<?php declare(strict_types=1);

namespace Labrador\HttpDummyApp;

use Amp\Http\Server\Session\LocalSessionStorage;
use Amp\Http\Server\Session\SessionFactory;
use Amp\Sync\LocalKeyedMutex;
use Cspray\AnnotatedContainer\Attribute\Service;
use Labrador\Http\ApplicationFeatures;

#[Service(primary: true)]
final class DummyApplicationFeatures implements ApplicationFeatures {

    public function getSessionFactory() : ?SessionFactory {
        return new SessionFactory(
            new LocalKeyedMutex(),
            new LocalSessionStorage()
        );
    }
}