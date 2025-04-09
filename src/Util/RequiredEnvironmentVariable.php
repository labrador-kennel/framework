<?php declare(strict_types=1);

namespace Labrador\Util;

use Labrador\Util\Exception\MissingRequiredEnvironmentVariable;

final class RequiredEnvironmentVariable {

    private function __construct() {}

    public static function get(string $var): string {
        $value = getenv($var);
        if ($value === false) {
            throw MissingRequiredEnvironmentVariable::fromEnvironmentVariableNotSet($var);
        }

        return $value;
    }

}