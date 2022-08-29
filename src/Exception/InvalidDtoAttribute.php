<?php

namespace Cspray\Labrador\Http\Exception;

class InvalidDtoAttribute extends Exception {

    public static function fromMultipleAttributes(string $classMethod, string $parameterName) : self {
        return new self(
            sprintf(
                'The parameter "%s" on %s declares multiple DTO Attributes but MUST contain only 1.',
                $parameterName,
                $classMethod
            )
        );
    }

}