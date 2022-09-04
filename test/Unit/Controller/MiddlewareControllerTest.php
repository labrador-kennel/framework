<?php declare(strict_types=1);


namespace Labrador\Http\Test\Unit\Controller;

use Labrador\Http\Controller\MiddlewareController;
use Labrador\Http\Test\Unit\Stub\RequestDecoratorMiddleware;
use Labrador\Http\Test\Unit\Stub\ResponseDecoratorMiddleware;
use Labrador\Http\Test\Unit\Stub\ToStringControllerStub;
use PHPUnit\Framework\TestCase;

class MiddlewareControllerTest extends TestCase {

    public function testToString() {
        $controller = new ToStringControllerStub('TheController');
        $middlewares = [
            new RequestDecoratorMiddleware(),
            new ResponseDecoratorMiddleware(),
        ];
        $subject = new MiddlewareController($controller, ...$middlewares);

        $expected = 'MiddlewareHandler<TheController, %s, %s>';
        $expected = sprintf($expected, RequestDecoratorMiddleware::class, ResponseDecoratorMiddleware::class);
        $actual = $subject->toString();
        $this->assertSame($expected, $actual);
    }
}
