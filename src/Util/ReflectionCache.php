<?php declare(strict_types=1);

namespace Labrador\Util;

use ReflectionClass;
use ReflectionException;
use SplObjectStorage;

final class ReflectionCache {

    /**
     * @var array<class-string, ReflectionClass>
     */
    private static array $cache = [];

    /**
     * @param class-string $class
     * @return ReflectionClass
     * @throws ReflectionException
     */
    public static function fromClass(string $class) : ReflectionClass {
        return self::$cache[$class] ??= new ReflectionClass($class);
    }
}
