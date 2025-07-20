<?php declare(strict_types=1);

namespace Labrador\TestHelper;

use Amp\Http\Server\Session\SessionIdGenerator;

final class KnownSessionIdGenerator implements SessionIdGenerator {

    private const ID_PREFIX = 'known-session-id';

    private int $counter = 0;

    public function generate() : string {
        return self::ID_PREFIX . '-' . $this->counter++;
    }

    public function validate(string $id) : bool {
        return str_starts_with($id, self::ID_PREFIX);
    }

    public function currentId() : string {
        // the current id is always whatever the counter is before it has been incremented
        // if we have already started incrementing, then we need to take off 1 to account for this
        $counter = $this->counter > 0 ? $this->counter - 1 : 0;
        return self::ID_PREFIX . '-' . $counter;
    }
}
