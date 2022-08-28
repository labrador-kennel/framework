<?php

namespace Cspray\Labrador\Http\Exception;

final class MissingMethod extends Exception {

    public static function fromClassDoesNotHaveMethod(string $class, string $method) : self {
        return new self(
            sprintf('The method "%s" does not exist on class %s.', $method, $class)
        );
    }

}