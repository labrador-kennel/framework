<?php declare(strict_types=1);

namespace Labrador\Web\TestHelper;

use Amp\Http\Server\Session\LocalSessionStorage;
use Amp\Http\Server\Session\SessionFactory;
use Amp\Http\Server\Session\SessionIdGenerator;
use Amp\Http\Server\Session\SessionMiddleware;
use Amp\Http\Server\Session\SessionStorage;

final class TestSessionMiddleware {

    private function __construct() {
    }

    public static function create(
        SessionStorage $sessionStorage = new LocalSessionStorage(),
        SessionIdGenerator $sessionIdGenerator = new KnownSessionIdGenerator()
    ) : SessionMiddleware {
        return new SessionMiddleware(
            new SessionFactory(
                storage: $sessionStorage,
                idGenerator: $sessionIdGenerator
            ),
        );
    }
}
