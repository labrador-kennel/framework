<?php declare(strict_types=1);

namespace Labrador\Test\Unit\Security;

use Labrador\Security\RandomEngineTokenGenerator;
use PHPUnit\Framework\TestCase;
use Random\Engine;

class RandomEngineTokenGeneratorTest extends TestCase {

    public function testValueIsConvertedToHexadecimal() : void {
        $engine = $this->getMockBuilder(Engine::class)->getMock();
        $engine->expects($this->once())->method('generate')->willReturn('known-value');

        $subject = new RandomEngineTokenGenerator($engine);

        self::assertSame(bin2hex('known-value'), $subject->generateToken());
    }

}