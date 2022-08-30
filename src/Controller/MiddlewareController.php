<?php declare(strict_types=1);

namespace Cspray\Labrador\Http\Controller;

use Amp\Http\Server\Middleware;
use Amp\Http\Server\Request;
use Amp\Http\Server\RequestHandler;
use Amp\Http\Server\Response;

final class MiddlewareController implements Controller {

    private Controller $controller;
    /** @var Middleware[] */
    private array $middlewares;
    private RequestHandler $stack;

    public function __construct(Controller $controller, Middleware ...$middlewares) {
        $this->controller = $controller;
        $this->middlewares = $middlewares;
        $this->stack = Middleware\stack($this->controller, ...$middlewares);
    }

    public function getMiddlewares() : array {
        return $this->middlewares;
    }

    /**
     * @param Request $request
     *
     * @return \Amp\Http\Server\Response
     */
    public function handleRequest(Request $request) : Response {
        return $this->stack->handleRequest($request);
    }

    public function toString() : string {
        $middlewareDescription = implode(', ', array_map(function(Middleware $middleware) {
            return get_class($middleware);
        }, $this->middlewares));

        return sprintf('MiddlewareHandler<%s, %s>', $this->controller->toString(), $middlewareDescription);
    }
}
