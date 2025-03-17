<?php declare(strict_types=1);

namespace Labrador\Test\Helper;

use Amp\Http\Server\Session\LocalSessionStorage;
use Amp\Http\Server\Session\SessionFactory;
use Amp\Http\Server\Session\SessionMiddleware;
use Amp\Http\Server\Session\SessionStorage;
use Cspray\AnnotatedContainer\Attribute\Service;
use Labrador\Web\Session\SessionMiddlewareFactory;

#[Service]
class TestSessionMiddlewareFactory implements SessionMiddlewareFactory {

    public SessionStorage $sessionStorage;

    public function createSessionMiddleware() : SessionMiddleware {
        $this->sessionStorage = new LocalSessionStorage();

        return new SessionMiddleware(
            new SessionFactory(storage: $this->sessionStorage)
        );
    }
}
