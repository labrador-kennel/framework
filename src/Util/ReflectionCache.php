<?php declare(strict_types=1);

namespace Labrador\Util;

final class ReflectionCache {

    private static array $cache = [];

    /**
     * @param class-string $class
     * @return \ReflectionClass
     */
    public static function fromClass(string $class) : \ReflectionClass {
        return self::$cache[$class] ??= new \ReflectionClass($class);
    }

}
