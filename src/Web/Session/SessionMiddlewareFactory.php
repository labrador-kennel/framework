<?php declare(strict_types=1);

namespace Labrador\Web\Session;

use Amp\Http\Server\Session\SessionMiddleware;
use Cspray\AnnotatedContainer\Attribute\Service;
use Cspray\AnnotatedContainer\Attribute\ServiceDelegate;

#[Service]
interface SessionMiddlewareFactory {

    #[ServiceDelegate]
    public function createSessionMiddleware() : SessionMiddleware;
}
