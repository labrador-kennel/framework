<?php declare(strict_types=1);

namespace Labrador\Validation;

use Respect\Validation\Validatable;

interface MessageGenerator {

    public function getMessage(Validatable $rule, object $object, string $property, mixed $value) : string;

}