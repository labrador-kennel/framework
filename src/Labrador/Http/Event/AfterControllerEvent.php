<?php

/**
 * Event triggered after the successful controller for a given request has been
 * invoked.
 * 
 * @license See LICENSE in source root
 */

namespace Labrador\Http\Event;

use Labrador\Http\Engine;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class AfterControllerEvent extends HttpEvent {

    private $controller;

    public function __construct(Request $req, Response $res, callable $controller) {
        parent::__construct($req, Engine::AFTER_CONTROLLER_EVENT);
        $this->setResponse($res);
        $this->controller = $controller;
    }

    public function getController() {
        return $this->controller;
    }

}
