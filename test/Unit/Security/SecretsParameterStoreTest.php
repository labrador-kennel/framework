<?php declare(strict_types=1);

namespace Labrador\Test\Unit\Security;

use Labrador\Security\InMemorySecretsSource;
use Labrador\Security\SecretsParameterStore;
use PHPUnit\Framework\TestCase;
use function Cspray\Typiphy\stringType;

final class SecretsParameterStoreTest extends TestCase {

    public function testGetNameReturnsSecrets() : void {
        $subject = new SecretsParameterStore();
        self::assertSame('secrets', $subject->getName());
    }

    public function testDotSeparatedStringWithNoStoresReturnsNull() : void {
        $subject = new SecretsParameterStore();
        self::assertNull($subject->fetch(stringType(), 'foo.bar.not-found'));
    }

    public function testDotSeparatedStringWithStoreAndValueReturnsCorrectString() : void {
        $subject = new SecretsParameterStore(
            new InMemorySecretsSource('foo', ['bar' => 'found'])
        );

        self::assertSame('found', $subject->fetch(stringType(), 'foo.bar'));
    }

}