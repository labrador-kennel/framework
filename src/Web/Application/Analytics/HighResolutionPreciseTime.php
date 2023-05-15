<?php declare(strict_types=1);

namespace Labrador\Web\Application\Analytics;

use Cspray\AnnotatedContainer\Attribute\Service;

#[Service]
class HighResolutionPreciseTime implements PreciseTime {

    public function now() : int|float {
        return hrtime(true);
    }
}