<?php declare(strict_types=1);

namespace Labrador\Http\Application;

use Amp\Http\Server\Session\SessionMiddleware;
use Cspray\AnnotatedContainer\Attribute\Service;

#[Service]
interface ApplicationFeatures {

    public function getSessionMiddleware() : ?SessionMiddleware;

    public function autoRedirectHttpToHttps() : bool;

}