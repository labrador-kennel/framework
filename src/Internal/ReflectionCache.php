<?php declare(strict_types=1);

namespace Labrador\Http\Internal;

use ReflectionClass;

final class ReflectionCache {

    private static array $cache = [];

    public static function reflectionClass(string|object $class) : ReflectionClass {
        $key = is_object($class) ? $class::class : $class;
        if (!array_key_exists($key, self::$cache)) {
            self::$cache[$key] = new ReflectionClass($class);
        }

        return self::$cache[$key];
    }

}
