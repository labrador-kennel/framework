<?php

namespace Labrador\Http\Test\Unit\DependencyInjection;

use Labrador\Http\DependencyInjection\AutowireObserver;
use Labrador\Http\DependencyInjection\HttpInitializer;
use Labrador\Http\DependencyInjection\ThirdPartyDefinitionProvider;
use PHPUnit\Framework\TestCase;

final class HttpInitializerTest extends TestCase {

    public function testGetPackageName() : void {
        $actual = (new HttpInitializer())->getPackageName();

        self::assertSame('labrador-kennel/http', $actual);
    }

    public function testGetScanDirectories() : void {
        $actual = (new HttpInitializer())->getRelativeScanDirectories();

        self::assertSame(['src'], $actual);
    }

    public function testGetObservers() : void {
        $actual = (new HttpInitializer())->getObserverClasses();

        self::assertSame([AutowireObserver::class], $actual);
    }

    public function testGetDefinitionProvider() : void {
        $actual = (new HttpInitializer())->getDefinitionProviderClass();

        self::assertSame(ThirdPartyDefinitionProvider::class, $actual);
    }

}