<?php declare(strict_types=1);

namespace Labrador\TestHelper;

use Amp\Http\Server\Session\SessionIdGenerator;

final class KnownSessionIdGenerator implements SessionIdGenerator {

    public const ID_PREFIX = 'known-session-id';

    private int $counter = 0;

    public function generate() : string {
        return self::ID_PREFIX . '-' . $this->counter++;
    }

    public function validate(string $id) : bool {
        $counter = $this->counter > 0 ? $this->counter - 1 : 0;
        return self::ID_PREFIX . '-' . ($counter) === $id;
    }
}
