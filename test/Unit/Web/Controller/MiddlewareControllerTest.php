<?php declare(strict_types=1);


namespace Labrador\Test\Unit\Web\Controller;

use Labrador\Test\Unit\Web\Stub\RequestDecoratorMiddleware;
use Labrador\Test\Unit\Web\Stub\ResponseDecoratorMiddleware;
use Labrador\Test\Unit\Web\Stub\ToStringControllerStub;
use Labrador\Web\Controller\MiddlewareController;
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
