<?php declare(strict_types=1);

namespace Labrador\Test\Unit\Validation;

use Labrador\Test\Unit\Validation\Stub\EntityWithMultiplePropertiesAndSingleValidate;
use Labrador\Test\Unit\Validation\Stub\EntityWithNoValidateAttributes;
use Labrador\Test\Unit\Validation\Stub\EntityWithSinglePropertyAndMultipleValidate;
use Labrador\Test\Unit\Validation\Stub\EntityWithSinglePropertyAndSingleValidate;
use Labrador\Validation\Exception\ValidateAttributeNotFound;
use Labrador\Validation\PropertyValidateMap;
use Labrador\Validation\DefaultMessageGenerator;
use Labrador\Validation\Validate;
use PHPUnit\Framework\TestCase;
use Respect\Validation\Rules\Alnum;
use Respect\Validation\Rules\Alpha;
use Respect\Validation\Rules\Equals;
use Respect\Validation\Rules\NotBlank;

final class PropertyValidateMapTest extends TestCase {

    public function testClassHasNoPropertiesAttributedWithValidateThrowsException() : void {
        $this->expectException(ValidateAttributeNotFound::class);
        $this->expectExceptionMessage('The class "' . EntityWithNoValidateAttributes::class . '" does not have any properties attributed with #[Validate].');

        PropertyValidateMap::fromAttributedProperties(EntityWithNoValidateAttributes::class);
    }

    public function testClassHasPropertyWithOneValidateAttributeHasCorrectPropertyValidateMap() : void {
        $subject = PropertyValidateMap::fromAttributedProperties(EntityWithSinglePropertyAndSingleValidate::class);

        self::assertCount(1, $subject);

        $subjectArray = iterator_to_array($subject);

        self::assertArrayHasKey('foo', $subjectArray);

        self::assertIsArray($subjectArray['foo']);
        self::assertCount(1, $subjectArray['foo']);
        self::assertContainsOnlyInstancesOf(Validate::class, $subjectArray['foo']);
        self::assertInstanceOf(Alpha::class, $subjectArray['foo'][0]->rule);
        self::assertInstanceOf(DefaultMessageGenerator::class, $subjectArray['foo'][0]->messageGenerator);
    }

    public function testClassHasPropertyWithMultipleValidateAttributeHasCorrectPropertyValidateMap() : void {
        $subject = PropertyValidateMap::fromAttributedProperties(EntityWithSinglePropertyAndMultipleValidate::class);

        self::assertCount(1, $subject);

        $subjectArray = iterator_to_array($subject);

        self::assertArrayHasKey('foo', $subjectArray);

        self::assertIsArray($subjectArray['foo']);
        self::assertCount(2, $subjectArray['foo']);
        self::assertContainsOnlyInstancesOf(Validate::class, $subjectArray['foo']);

        self::assertInstanceOf(Alpha::class, $subjectArray['foo'][0]->rule);
        self::assertInstanceOf(DefaultMessageGenerator::class, $subjectArray['foo'][0]->messageGenerator);

        self::assertInstanceOf(Equals::class, $subjectArray['foo'][1]->rule);
        self::assertInstanceOf(DefaultMessageGenerator::class, $subjectArray['foo'][1]->messageGenerator);
    }

    public function testClassHasMultiplePropertiesWithSingleValidateAttributeHasCorrectPropertyValidateMap() : void {
        $subject = PropertyValidateMap::fromAttributedProperties(EntityWithMultiplePropertiesAndSingleValidate::class);

        self::assertCount(2, $subject);

        $subjectArray = iterator_to_array($subject);

        self::assertArrayNotHasKey('foo', $subjectArray);
        self::assertArrayHasKey('bar', $subjectArray);
        self::assertArrayHasKey('baz', $subjectArray);

        self::assertIsArray($subjectArray['bar']);
        self::assertCount(1, $subjectArray['bar']);
        self::assertContainsOnlyInstancesOf(Validate::class, $subjectArray['bar']);

        self::assertInstanceOf(NotBlank::class, $subjectArray['bar'][0]->rule);
        self::assertInstanceOf(DefaultMessageGenerator::class, $subjectArray['bar'][0]->messageGenerator);

        self::assertIsArray($subjectArray['baz']);
        self::assertCount(1, $subjectArray['baz']);
        self::assertContainsOnlyInstancesOf(Validate::class, $subjectArray['baz']);

        self::assertInstanceOf(Alnum::class, $subjectArray['baz'][0]->rule);
        self::assertInstanceOf(DefaultMessageGenerator::class, $subjectArray['baz'][0]->messageGenerator);
    }
}
