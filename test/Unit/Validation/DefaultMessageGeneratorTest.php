<?php declare(strict_types=1);

namespace Labrador\Test\Unit\Validation;

use Labrador\Test\Unit\Validation\Stub\EntityWithSinglePropertyAndSingleValidate;
use Labrador\Validation\DefaultMessageGenerator;
use PHPUnit\Framework\TestCase;
use Respect\Validation\Rules\Alpha;

final class DefaultMessageGeneratorTest extends TestCase {

    public function testHasDefaultMessageDetailingWhatObjectPropertyViolatedWhichRule() : void {
        $subject = new DefaultMessageGenerator();
        $expected = 'The value in ' . EntityWithSinglePropertyAndSingleValidate::class . '::foo does not validate against ' . Alpha::class;

        $object = new EntityWithSinglePropertyAndSingleValidate('whatever');

        self::assertEquals($expected, $subject->getMessage(new Alpha(), $object, 'foo', 'whatever'));
    }

}