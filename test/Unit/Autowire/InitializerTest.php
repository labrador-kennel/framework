<?php

namespace Labrador\Http\Test\Unit\Autowire;

use Labrador\Http\Autowire\DefinitionProvider;
use Labrador\Http\Autowire\Initializer;
use Labrador\Http\Autowire\Observer;
use PHPUnit\Framework\TestCase;

final class InitializerTest extends TestCase {

    public function testGetPackageName() : void {
        $actual = (new Initializer())->getPackageName();

        self::assertSame('labrador-kennel/http', $actual);
    }

    public function testGetScanDirectories() : void {
        $actual = (new Initializer())->getRelativeScanDirectories();

        self::assertSame(['src'], $actual);
    }

    public function testGetObservers() : void {
        $actual = (new Initializer())->getObserverClasses();

        self::assertSame([Observer::class], $actual);
    }

    public function testGetDefinitionProvider() : void {
        $actual = (new Initializer())->getDefinitionProviderClass();

        self::assertSame(DefinitionProvider::class, $actual);
    }

}