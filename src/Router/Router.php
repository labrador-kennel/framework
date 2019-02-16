<?php declare(strict_types=1);

namespace Cspray\Labrador\Http\Router;

use Amp\Http\Server\Middleware;
use Amp\Http\Server\Request;
use Cspray\Labrador\Http\Controller\Controller;

/**
 * An interface that is responsible for matching a given Request by HTTP method and URL to a Controller instance and an
 * optional set of Middleware.
 *
 * @license See LICENSE in source root
 */
interface Router {

    /**
     * Add a Controller and an optional set of Middleware that should be returned from match() for a Request that matches
     * the given $method and $pattern.
     *
     * @param string $method
     * @param string $pattern
     * @param Controller $controller
     * @param Middleware[] $middlewares
     * @return void
     */
    public function addRoute(string $method, string $pattern, Controller $controller, Middleware ...$middlewares) : void;

    /**
     * If the Router implementation cannot reasonably match the $request against a configured Controller the default
     * Controller should be returned; if a default Controller has not been set the Router implementation should take
     * steps to return a reasonable default Controller.
     *
     * @param Request $request
     * @return Controller
     */
    public function match(Request $request) : Controller;

    /**
     * Set the Controller that should be used if the Request being matched does nto have a Controller added for it.
     *
     * @param Controller $controller
     */
    public function setNotFoundController(Controller $controller) : void;

    /**
     * If a Controller has not been set with setNotFoundController the Router implementation should take steps to return
     * a reasonable default Controller implementation.
     *
     * @return Controller
     */
    public function getNotFoundController() : Controller;

    /**
     * Set the Controller that should be used if the Request being matched has a corresponding URL but the HTTP method
     * used is not allowed.
     *
     * @param Controller $controller
     */
    public function setMethodNotAllowedController(Controller $controller) : void;

    /**
     * If a Controller has not been set with setNotFoundController the Router implementation should take steps to return
     * a reasonable default Controller implementation.
     *
     * @return Controller
     */
    public function getMethodNotAllowedController() : Controller;

    /**
     * Return an array of Route objects that have been added to this Router.
     *
     * @return Route[]
     */
    public function getRoutes() : array;
}
