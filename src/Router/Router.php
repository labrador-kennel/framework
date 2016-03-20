<?php

declare(strict_types=1);

/**
 * Interface to determine the controller to invoke for a given Request.
 *
 * @license See LICENSE in source root
 */

namespace Cspray\Labrador\Http\Router;

use Psr\Http\Message\ServerRequestInterface;

/**
 * The $handler set in methods can be an arbitrary value; the value that you set
 * should be parseable by the HandlerResolver you use when wiring up Labrador.
 */
interface Router {

    /**
     * @param string $method
     * @param string $pattern
     * @param mixed $handler
     * @return $this
     */
    public function addRoute(string $method, string $pattern, $handler);

    /**
     * Should always return a ResolvedRoute that includes the controller that
     * should be invoked
     *
     * @param ServerRequestInterface $request
     * @return ResolvedRoute
     */
    public function match(ServerRequestInterface $request) : ResolvedRoute;

    /**
     * @return Route[]
     */
    public function getRoutes() : array;

}
