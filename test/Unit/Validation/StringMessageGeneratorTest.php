<?php declare(strict_types=1);

namespace Labrador\Test\Unit\Validation;

use Labrador\Validation\StringMessageGenerator;
use PHPUnit\Framework\TestCase;
use Respect\Validation\Rules\Alpha;

final class StringMessageGeneratorTest extends TestCase {

    public function testStringPassedToConstructorReturnedFromGetMessage() : void {
        $subject = new StringMessageGenerator('Foo string');

        self::assertSame('Foo string', $subject->getMessage(
            new Alpha(),
            new \stdClass(),
            'property',
            'value'
        ));
    }
}
