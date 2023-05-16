<?php

namespace Labrador\Test\Unit\Web\Controller;

use Labrador\Web\Controller\SelfDescribingController;
use PHPUnit\Framework\TestCase;

final class SelfDescribingControllerTest extends TestCase {

    public function testToString() : void {
        $subject = $this->getMockForAbstractClass(SelfDescribingController::class);

        self::assertSame($subject::class, $subject->toString());
    }

}