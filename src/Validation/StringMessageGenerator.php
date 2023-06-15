<?php declare(strict_types=1);

namespace Labrador\Validation;

use Respect\Validation\Validatable;

final class StringMessageGenerator implements MessageGenerator {

    public function __construct(
        private readonly string $message
    ) {}

    public function getMessage(Validatable $rule, object $object, string $property, mixed $value) : string {
        return $this->message;
    }
}