<?php declare(strict_types=1);

namespace Labrador\TestHelper;

use Cspray\AnnotatedContainer\Attribute\Service;
use DateTimeImmutable;
use Psr\Clock\ClockInterface;

#[Service(profiles: [TestProfile::NAME])]
final class FixedClock implements ClockInterface {

    public const DEFAULT_NOW = '2020-01-01 12:00:00';

    private DateTimeImmutable $now;

    public function __construct() {
        $this->now = new DateTimeImmutable(self::DEFAULT_NOW, new \DateTimeZone('UTC'));
    }

    public function now() : DateTimeImmutable {
        return $this->now;
    }

    public function moveNow(DateTimeImmutable $dateTimeImmutable) : void {
        $this->now = $dateTimeImmutable;
    }

    public function moveForwardInTime(\DateInterval $dateInterval) : void {
        $this->now = $this->now->add($dateInterval);
    }

    public function moveBackwardInTime(\DateInterval $dateInterval): void {
        $this->now = $this->now->sub($dateInterval);
    }
}
