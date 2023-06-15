<?php declare(strict_types=1);

namespace Labrador\Validation\Exception;

use Labrador\Exception\Exception;

final class ValidateAttributeNotFound extends Exception {

    /**
     * @param class-string $class
     */
    public static function fromClassHasNoPropertiesAttributed(string $class) : self {
        return new self(sprintf(
            'The class "%s" does not have any properties attributed with #[Validate].',
            $class
        ));
    }

}