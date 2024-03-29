<?php declare(strict_types=1);

namespace Labrador\Web\Controller;

use Amp\Http\Server\Middleware;
use Amp\Http\Server\Request;
use Amp\Http\Server\RequestHandler;
use Amp\Http\Server\Response;
use function Amp\Http\Server\Middleware\stackMiddleware;

final class MiddlewareController implements Controller {

    public readonly Controller $controller;
    /** @var Middleware[] */
    public readonly array $middlewares;
    private RequestHandler $stack;

    public function __construct(Controller $controller, Middleware ...$middlewares) {
        $this->controller = $controller;
        $this->middlewares = $middlewares;
        $this->stack = stackMiddleware($this->controller, ...$middlewares);
    }

    /**
     * @param Request $request
     *
     * @return Response
     */
    public function handleRequest(Request $request) : Response {
        return $this->stack->handleRequest($request);
    }

    public function toString() : string {
        $middlewareDescription = implode(', ', array_map(static function(Middleware $middleware) {
            return get_class($middleware);
        }, $this->middlewares));

        return sprintf('MiddlewareHandler<%s, %s>', $this->controller->toString(), $middlewareDescription);
    }
}
