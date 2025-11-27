<?php declare(strict_types=1);

namespace Labrador\Web\Router;

use Amp\Http\Server\Middleware;
use Amp\Http\Server\Request;
use Amp\Http\Server\RequestHandler;
use Cspray\AnnotatedContainer\Attribute\Service;
use Labrador\Web\Router\Mapping\RequestMapping;

/**
 * An interface that is responsible for matching a given Request by HTTP method and URL to a RequestHandler instance
 * and an optional set of Middleware.
 */
#[Service]
interface Router {

    /**
     * Add a RequestHandler and an optional set of Middleware that should be returned from match() for a Request that
     * matches the given $method and $pattern.
     */
    public function addRoute(
        RequestMapping $requestMapping,
        RequestHandler $requestHandler,
        Middleware ...$middlewares
    ) : Route;

    public function match(Request $request) : RoutingResolution;

    /**
     * Return an array of Route objects that have been added to this Router.
     *
     * @return Route[]
     */
    public function getRoutes() : array;
}
