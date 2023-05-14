<?php declare(strict_types=1);

namespace Labrador\Test\Unit\Controller\Dto;

use Labrador\Web\Controller\Dto\RouteParam;
use Labrador\Web\Exception\InvalidDtoAttribute;
use PHPUnit\Framework\TestCase;

final class RouteParamTest extends TestCase {

    public function testEmptyStringThrowsException() : void {
        $this->expectException(InvalidDtoAttribute::class);
        $this->expectExceptionMessage('A DTO RouteParam name MUST NOT be empty.');

        new RouteParam('   ');
    }

}