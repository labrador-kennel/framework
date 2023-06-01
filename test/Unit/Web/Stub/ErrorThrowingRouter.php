<?php declare(strict_types=1);

namespace Labrador\Test\Unit\Web\Stub;

use Amp\Http\Server\Middleware;
use Amp\Http\Server\Request;
use Labrador\Web\Controller\Controller;
use Labrador\Web\Router\Mapping\RequestMapping;
use Labrador\Web\Router\Route;
use Labrador\Web\Router\Router;
use Labrador\Web\Router\RoutingResolution;
use Throwable;

final class ErrorThrowingRouter implements Router {

    public function __construct(
        private readonly Throwable $throwable
    ) {}

    public function addRoute(RequestMapping $requestMapping, Controller $controller, Middleware ...$middlewares) : Route {
        return new Route($requestMapping, $controller);
    }

    public function match(Request $request) : RoutingResolution {
        throw $this->throwable;
    }

    public function getRoutes() : array {
        return [];
    }
}