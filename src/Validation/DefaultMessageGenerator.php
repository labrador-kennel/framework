<?php declare(strict_types=1);

namespace Labrador\Validation;

use Respect\Validation\Validatable;

final class DefaultMessageGenerator implements MessageGenerator {

    public function getMessage(Validatable $rule, object $object, string $property, mixed $value) : string {
        return sprintf(
            'The value in %s::%s does not validate against %s',
            $object::class,
            $property,
            $rule::class
        );
    }

}
