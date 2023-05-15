<?php declare(strict_types=1);

namespace Labrador\Internal;

use ReflectionClass;
use ReflectionException;

final class ReflectionCache {

    /**
     * @var array<class-string, ReflectionClass>
     */
    private static array $cache = [];

    /**
     * @param class-string|object $class
     * @return ReflectionClass
     * @throws ReflectionException
     */
    public static function reflectionClass(string|object $class) : ReflectionClass {
        $key = is_object($class) ? $class::class : $class;
        if (!array_key_exists($key, self::$cache)) {
            self::$cache[$key] = new ReflectionClass($class);
        }

        return self::$cache[$key];
    }

}
