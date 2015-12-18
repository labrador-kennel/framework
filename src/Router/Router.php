<?php

declare(strict_types=1);

/**
 * Interface to determine the controller to invoke for a given Request.
 * 
 * @license See LICENSE in source root
 */

namespace Cspray\Labrador\Http\Router;

use Symfony\Component\HttpFoundation\Request;

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
    function addRoute(string $method, string $pattern, $handler);

    /**
     * Should always return a ResolvedRoute that includes the controller that
     * should be invoked
     *
     * @param Request $request
     * @return ResolvedRoute
     */
    function match(Request $request) : ResolvedRoute;

    /**
     * @return Route[]
     */
    function getRoutes() : array;

} 
