<?php declare(strict_types=1);

namespace Cspray\Labrador\Http\Controller;

use Amp\Promise;
use Amp\Http\Server\{
    Middleware,
    Request,
};

class MiddlewareController implements Controller {

    private $controller;
    private $middlewares;
    private $stack;

    public function __construct(Controller $controller, Middleware ...$middlewares) {
        $this->controller = $controller;
        $this->middlewares = $middlewares;
        $this->stack = Middleware\stack($this->controller, ...$middlewares);
    }

    /**
     * @param Request $request
     *
     * @return Promise<\Amp\Http\Server\Response>
     */
    public function handleRequest(Request $request): Promise {
        return $this->stack->handleRequest($request);
    }

    public function toString(): string {
        $string = $this->controller->toString() . '<';
        $string .= implode(', ', array_map(function(Middleware $middleware) {
            return get_class($middleware);
        }, $this->middlewares));
        $string .= '>';
        return $string;
    }
}