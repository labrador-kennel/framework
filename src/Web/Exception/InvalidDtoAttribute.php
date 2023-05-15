<?php

namespace Labrador\Web\Exception;

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

    public static function fromRouteParamIsEmpty() : self {
        return new self('A DTO RouteParam name MUST NOT be empty.');
    }

}