<?php declare(strict_types=1);

namespace Labrador\Util\Exception;

use Labrador\Exception\Exception;

final class MissingRequiredEnvironmentVariable extends Exception {
    public static function fromEnvironmentVariableNotSet(string $variable) : self {
        return new self(sprintf(
            'The environment variable "%s" is not set and MUST BE present.',
            $variable
        ));
    }
}
