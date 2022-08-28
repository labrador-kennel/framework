<?php declare(strict_types=1);


namespace Cspray\Labrador\Http\Test\Unit\Controller;

use Cspray\Labrador\Http\Controller\MiddlewareController;
use Cspray\Labrador\Http\Test\Unit\Stub\RequestDecoratorMiddleware;
use Cspray\Labrador\Http\Test\Unit\Stub\ResponseDecoratorMiddleware;
use Cspray\Labrador\Http\Test\Unit\Stub\ToStringControllerStub;
use PHPUnit\Framework\TestCase;

class MiddlewareControllerTest extends TestCase {

    public function testToString() {
        $controller = new ToStringControllerStub('TheController');
        $middlewares = [
            new RequestDecoratorMiddleware(),
            new ResponseDecoratorMiddleware(),
        ];
        $subject = new MiddlewareController($controller, ...$middlewares);

        $expected = 'TheController<%s, %s>';
        $expected = sprintf($expected, RequestDecoratorMiddleware::class, ResponseDecoratorMiddleware::class);
        $actual = $subject->toString();
        $this->assertSame($expected, $actual);
    }
}
