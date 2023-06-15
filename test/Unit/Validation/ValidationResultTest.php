<?php declare(strict_types=1);

namespace Labrador\Test\Unit\Validation;

use Labrador\Validation\ValidationResult;
use PHPUnit\Framework\TestCase;

final class ValidationResultTest extends TestCase {

    public function testEmptyMapReturnsIsValid() : void {
        $subject = new ValidationResult([]);

        self::assertTrue($subject->isValid());
    }

    public function testEmptyMapReturnsEmptyListForPropertyMessages() : void {
        $subject = new ValidationResult([]);

        self::assertSame([], $subject->getMessages('property'));
    }

    public function testEmptyMapReturnsZeroCount() : void {
        $subject = new ValidationResult([]);

        self::assertCount(0, $subject);
    }

    public function testEmptyMapResultsInEmptyIterator() : void {
        $subject = iterator_to_array(new ValidationResult([]));

        self::assertSame([], $subject);
    }

    public function testMapHasPropertiesIsInvalid() : void {
        $subject = new ValidationResult([
            'property' => ['Message one']
        ]);

        self::assertFalse($subject->isValid());
    }

    public function testMapHasPropertiesReturnsCorrectMessages() : void {
        $subject = new ValidationResult([
            'harry' => ['Mack', 'freestyle', 'rapper']
        ]);

        self::assertSame(['Mack', 'freestyle', 'rapper'], $subject->getMessages('harry'));
    }

    public function testMapHasPropertiesReturnsCorrectCount() : void {
        $subject = new ValidationResult([
            'harry' => ['music', 'bars'],
            'mastodon' => ['fediverse'],
            'ada' => ['kitten', 'angel']
        ]);

        self::assertCount(3, $subject);
    }

    public function testMapHasPropertiesHasCorreectIterator() : void {
        $subject = iterator_to_array(new ValidationResult($expected = [
            'harry' => ['music', 'bars'],
            'mastodon' => ['fediverse'],
            'ada' => ['kitten', 'angel']
        ]));

        self::assertEquals($expected, $subject);
    }


}