<?php declare(strict_types=1);

namespace Labrador\Test\Unit\Validation;

use Labrador\Test\Unit\Validation\Stub\EntityWithSinglePropertyAndSingleValidate;
use Labrador\Validation\DefaultMessageGenerator;
use Labrador\Validation\MessageGenerator;
use Labrador\Validation\StringMessageGenerator;
use Labrador\Validation\Validate;
use PHPUnit\Framework\TestCase;
use Respect\Validation\Validatable;

final class ValidateTest extends TestCase {

    public function testValidateWithNullMessageGeneratorHasDefaultMessageGenerator() : void {
        $subject = new Validate($this->getMockBuilder(Validatable::class)->getMock());

        self::assertInstanceOf(DefaultMessageGenerator::class, $subject->messageGenerator);
    }

    public function testValidateWithStringMessageGeneratorHasCorrectTypeAndValue() : void {
        $subject = new Validate($rule = $this->getMockBuilder(Validatable::class)->getMock(), 'My custom message as a string');

        self::assertInstanceOf(StringMessageGenerator::class, $subject->messageGenerator);
        self::assertEquals('My custom message as a string', $subject->messageGenerator->getMessage(
            $rule, new EntityWithSinglePropertyAndSingleValidate('something'), 'foo', 'whatever'
        ));
    }

    public function testValidateWithMessageGeneratorReturnsThatInstance() : void {
        $subject = new Validate(
            $this->getMockBuilder(Validatable::class)->getMock(),
            $generator = $this->getMockBuilder(MessageGenerator::class)->getMock()
        );

        self::assertSame($generator, $subject->messageGenerator);
    }

}