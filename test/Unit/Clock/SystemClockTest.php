<?php declare(strict_types=1);

namespace Labrador\Test\Unit\Clock;

use Labrador\Clock\SystemClock;
use PHPUnit\Framework\TestCase;

class SystemClockTest extends TestCase {

    public function testClockReturnsNowWithUtcTimeZoneByDefault() : void {
        self::assertEqualsWithDelta(
            (new \DateTimeImmutable('now', new \DateTimeZone('UTC')))->getTimestamp(),
            (new SystemClock())->now()->getTimestamp(),
            0
        );
    }
}
