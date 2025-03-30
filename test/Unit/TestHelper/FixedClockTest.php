<?php declare(strict_types=1);

namespace Labrador\Test\Unit\TestHelper;

use Labrador\TestHelper\FixedClock;
use PHPUnit\Framework\TestCase;

class FixedClockTest extends TestCase {

    public function testDefaultNowHasCorrectDateTime() : void {
        self::assertSame(
            FixedClock::DEFAULT_NOW,
            (new FixedClock())->now()->format('Y-m-d H:i:s')
        );
    }

    public function testSuccessiveCallsToNowReturnSameObject() : void {
        $subject = new FixedClock();
        self::assertSame($subject->now(), $subject->now());
    }

    public function testMovingForwardInTimeAddsCorrectAmountToCurrentNow() : void {
        $subject = new FixedClock();
        $subject->moveForwardInTime(new \DateInterval('P1Y'));

        self::assertSame(
            '2021-01-01 12:00:00',
            $subject->now()->format('Y-m-d H:i:s')
        );
    }

    public function testMovingBackwardInTimeSubtractsCorrectAmountToCurrentNow() : void {
        $subject = new FixedClock();
        $subject->moveBackwardInTime(new \DateInterval('P1M'));

        self::assertSame(
            '2019-12-01 12:00:00',
            $subject->now()->format('Y-m-d H:i:s')
        );
    }

    public function testMoveNowReturnsInjectedDateTimeImmutable() : void {
        $subject = new FixedClock();
        $subject->moveNow($now = new \DateTimeImmutable());

        self::assertSame($now, $subject->now());
    }
}
