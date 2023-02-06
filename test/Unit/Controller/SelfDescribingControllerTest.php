<?php

namespace Labrador\Http\Test\Unit\Controller;

use Labrador\Http\Controller\SelfDescribingController;
use PHPUnit\Framework\TestCase;

final class SelfDescribingControllerTest extends TestCase {

    public function testToString() : void {
        $subject = $this->getMockForAbstractClass(SelfDescribingController::class);

        self::assertSame($subject::class, $subject->toString());
    }

}