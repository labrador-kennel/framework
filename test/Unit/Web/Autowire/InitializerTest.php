<?php

namespace Labrador\Test\Unit\Web\Autowire;

use Labrador\Web\Autowire\DefinitionProvider;
use Labrador\Web\Autowire\Initializer;
use Labrador\Web\Autowire\Observer;
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