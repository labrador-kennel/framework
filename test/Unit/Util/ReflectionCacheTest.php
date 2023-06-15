<?php declare(strict_types=1);

namespace Labrador\Test\Unit\Util;

use Labrador\Util\ReflectionCache;
use PHPUnit\Framework\TestCase;

final class ReflectionCacheTest extends TestCase {

    public function testCreatesClassReturnsSameReflectionInstance() : void {
        $a = ReflectionCache::fromClass($this::class);
        $b = ReflectionCache::fromClass($this::class);

        self::assertSame($a, $b);
    }

}