<?php

namespace Labrador\Test\Unit\Web\Autowire;

use Labrador\Web\Autowire\DefinitionProvider;
use Labrador\Web\Autowire\Initializer;
use Labrador\Web\Autowire\RegisterControllerAndMiddlewareListener;
use PHPUnit\Framework\TestCase;

final class InitializerTest extends TestCase {

    public function testGetPackageName() : void {
        $actual = (new Initializer())->packageName();

        self::assertSame('labrador-kennel/framework', $actual);
    }

    public function testGetScanDirectories() : void {
        $actual = (new Initializer())->relativeScanDirectories();

        self::assertSame(['src'], $actual);
    }

    public function testGetDefinitionProvider() : void {
        $actual = (new Initializer())->definitionProviderClass();

        self::assertSame(DefinitionProvider::class, $actual);
    }

}