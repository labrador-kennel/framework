<?php declare(strict_types=1);

namespace Labrador\Web\TestHelper;

use Amp\Http\Server\Session\SessionIdGenerator;

final class KnownSessionIdGenerator implements SessionIdGenerator {

    /**
     * @param non-empty-string $sessionId
     */
    public function __construct(
        private readonly string $sessionId = 'known-session-id'
    ) {
    }

    public function generate() : string {
        return $this->sessionId;
    }

    public function validate(string $id) : bool {
        return $this->sessionId === $id;
    }
}
