<?php declare(strict_types=1);

namespace Labrador\Http\Test\Unit\Stub;

use Labrador\Http\Application\Analytics\PreciseTime;

class KnownIncrementPreciseTime implements PreciseTime {

    private int $timesCalled = 0;

    public function __construct(
        private readonly int $start,
        private readonly int $increase
    ) {}

    public function now() : int|float {
        return $this->start + ($this->increase * $this->timesCalled++);
    }

}