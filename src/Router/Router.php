<?php declare(strict_types=1);

namespace Cspray\Labrador\Http\Router;

use Amp\Http\Server\Middleware;
use Amp\Http\Server\Request;
use Cspray\AnnotatedContainer\Attribute\Service;
use Cspray\Labrador\Http\Controller\Controller;

/**
 * An interface that is responsible for matching a given Request by HTTP method and URL to a Controller instance and an
 * optional set of Middleware.
 *
 * @license See LICENSE in source root
 */
#[Service]
interface Router {

    /**
     * Add a Controller and an optional set of Middleware that should be returned from match() for a Request that
     * matches
     * the given $method and $pattern.
     */
    public function addRoute(
        RequestMapping $requestMapping,
        Controller     $controller,
        Middleware... $middlewares
    ) : Route;

    /**
     * If the Router implementation cannot reasonably match the $request against a configured Controller the default
     * Controller should be returned; if a default Controller has not been set the Router implementation should take
     * steps to return a reasonable default Controller.
     */
    public function match(Request $request) : RoutingResolution;

    /**
     * Return an array of Route objects that have been added to this Router.
     *
     * @return Route[]
     */
    public function getRoutes() : array;
}
