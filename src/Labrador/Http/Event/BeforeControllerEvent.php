<?php

/**
 * Event triggered when a route was successfully routed to a controller and before
 * that controller is invoked.
 * 
 * @license See LICENSE in source root
 */

namespace Labrador\Http\Event;

use Labrador\Http\Engine;
use Symfony\Component\HttpFoundation\Request;

class BeforeControllerEvent extends HttpEvent {

    private $controller;

    public function __construct(Request $request, callable $controller) {
        parent::__construct($request, Engine::BEFORE_CONTROLLER_EVENT);
        $this->controller = $controller;
    }

    public function getController() {
        return $this->controller;
    }

    public function setController(callable $controller) {
        $this->controller = $controller;
    }

}
