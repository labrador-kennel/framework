<?php

declare(strict_types=1);

/**
 * Interface to determine the controller to invoke for a given Request.
 *
 * @license See LICENSE in source root
 */

namespace Cspray\Labrador\Http\Router;

use Amp\Http\Server\Request;
use Cspray\Labrador\Http\Controller\Controller;

/**
 * The $handler set in methods can be an arbitrary value; the value that you set
 * should be parseable by the HandlerResolver you use when wiring up Labrador.
 */
interface Router {

    /**
     * @param string $method
     * @param string $pattern
     * @param Controller $controller
     * @return void
     */
    public function addRoute(string $method, string $pattern, Controller $controller) : void;

    /**
     * @param Request $request
     * @return Controller
     */
    public function match(Request $request) : Controller;

    /**
     * @return Route[]
     */
    public function getRoutes() : array;

}
