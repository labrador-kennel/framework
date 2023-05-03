<?php declare(strict_types=1);

namespace Labrador\Http\Application\Analytics;

interface PreciseTime {

    public function now() : int|float;

}
