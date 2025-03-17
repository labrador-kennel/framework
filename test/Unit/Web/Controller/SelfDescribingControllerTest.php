<?php

namespace Labrador\Test\Unit\Web\Controller;

use Amp\Http\Server\Request;
use Amp\Http\Server\Response;
use Labrador\Web\Controller\SelfDescribingController;
use PHPUnit\Framework\TestCase;

final class SelfDescribingControllerTest extends TestCase {

    public function testToString() : void {
        $subject = new class extends SelfDescribingController {
            public function handleRequest(Request $request) : Response {
                return new Response();
            }
        };

        self::assertSame($subject::class, $subject->toString());
    }
}
