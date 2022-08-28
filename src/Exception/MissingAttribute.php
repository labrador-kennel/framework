<?php

namespace Cspray\Labrador\Http\Exception;

final class MissingAttribute extends Exception {

    public static function fromAttributeNotFoundOnClass(string $class, string $attributeType) : self {
        return new self(
            sprintf('All objects passed to %s must be annotated with %s Attribute.', $class, $attributeType)
        );
    }

}