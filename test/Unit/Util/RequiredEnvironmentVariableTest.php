<?php declare(strict_types=1);

namespace Labrador\Test\Unit\Util;

use Labrador\Util\Exception\MissingRequiredEnvironmentVariable;
use Labrador\Util\RequiredEnvironmentVariable;
use PHPUnit\Framework\TestCase;

class RequiredEnvironmentVariableTest extends TestCase {

    public function testEnvironmentVariableNotSetThrowsException() : void {
        $this->expectException(MissingRequiredEnvironmentVariable::class);
        $this->expectExceptionMessage(
            'The environment variable "ENV_VAR" is not set and MUST BE present.'
        );

        putenv('ENV_VAR');
        RequiredEnvironmentVariable::get('ENV_VAR');
    }

    public function testEnvironmentVariableSetReturnsCorrectValue() : void {
        putenv('ENV_VAR=set-value');
        self::assertSame(
            'set-value',
            RequiredEnvironmentVariable::get('ENV_VAR')
        );
    }

}