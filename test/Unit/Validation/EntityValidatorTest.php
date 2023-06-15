<?php declare(strict_types=1);

namespace Labrador\Test\Unit\Validation;

use Labrador\Test\Unit\Validation\Stub\EntityValidatorStub;
use Labrador\Test\Unit\Validation\Stub\EntityWithSinglePropertyAndSingleValidate;
use PHPUnit\Framework\TestCase;
use Respect\Validation\Rules\Alpha;

final class EntityValidatorTest extends TestCase {

    public function testEntityWithSingleValidatedProperty() : void {
        $subject = new EntityValidatorStub(EntityWithSinglePropertyAndSingleValidate::class);

        $entity = new EntityWithSinglePropertyAndSingleValidate('not alphabetic 1234');

        $results = $subject->validate($entity);

        self::assertFalse($results->isValid());
        self::assertSame([sprintf(
            'The value in %s::foo does not validate against %s',
            EntityWithSinglePropertyAndSingleValidate::class,
            Alpha::class
        )], $results->getMessages('foo'));
    }

}