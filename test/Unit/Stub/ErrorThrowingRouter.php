<?php declare(strict_types=1);

namespace Labrador\Http\Test\Unit\Stub;

use Amp\Http\Server\Middleware;
use Amp\Http\Server\Request;
use Labrador\Http\Controller\Controller;
use Labrador\Http\Router\RequestMapping;
use Labrador\Http\Router\Route;
use Labrador\Http\Router\Router;
use Labrador\Http\Router\RoutingResolution;
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