<?php declare(strict_types=1);

namespace Labrador\Util;

use ReflectionClass;
use ReflectionException;

final class ReflectionCache {

    /**
     * @var array{class-string, ReflectionClass}
     */
    private static array $cache = [];

    /**
     * @param class-string $class
     * @return ReflectionClass
     * @throws ReflectionException
     */
    public static function fromClass(string $class) : ReflectionClass {
        $reflection = self::$cache[$class] ??= new ReflectionClass($class);

        assert($reflection instanceof ReflectionClass);

        return $reflection;
    }

}
