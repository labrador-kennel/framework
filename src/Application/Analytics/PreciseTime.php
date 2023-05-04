<?php declare(strict_types=1);

namespace Labrador\Http\Application\Analytics;

use Cspray\AnnotatedContainer\Attribute\Service;

#[Service]
interface PreciseTime {

    public function now() : int|float;

}
