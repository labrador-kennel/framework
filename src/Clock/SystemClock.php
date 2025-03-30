<?php declare(strict_types=1);

namespace Labrador\Clock;

use Cspray\AnnotatedContainer\Attribute\Service;
use DateTimeImmutable;
use Psr\Clock\ClockInterface;

#[Service]
final class SystemClock implements ClockInterface {

    public function now() : DateTimeImmutable {
        return new DateTimeImmutable('now', new \DateTimeZone('UTC'));
    }
}
