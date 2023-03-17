<?php declare(strict_types=1);

namespace Labrador\Http;

use Amp\Http\Server\Session\SessionFactory;
use Cspray\AnnotatedContainer\Attribute\Service;

#[Service]
class NoApplicationFeatures implements ApplicationFeatures {

    public function getSessionFactory() : ?SessionFactory {
        return null;
    }
}