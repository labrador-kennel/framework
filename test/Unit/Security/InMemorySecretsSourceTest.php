<?php declare(strict_types=1);

namespace Labrador\Test\Unit\Security;

use Labrador\Security\InMemorySecretsSource;
use PHPUnit\Framework\TestCase;

final class InMemorySecretsSourceTest extends TestCase {

    public function testGetNameReturnsValueInjectedInConstructor() : void {
        $subject = new InMemorySecretsSource('subject', []);

        self::assertSame('subject', $subject->getName());
    }

    public function testGetDataReturnsValuesInjectedInConstructor() : void {
        $subject = new InMemorySecretsSource('subject', $expected = ['foo', 'bar']);

        self::assertSame($expected, $subject->getData());
    }

}