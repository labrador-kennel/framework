<?php

declare(strict_types=1);

/**
 * Event triggered after the successful controller for a given request has been
 * invoked.
 *
 * @license See LICENSE in source root
 */

namespace Cspray\Labrador\Http\Event;

use Cspray\Labrador\Http\Engine;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class AfterControllerEvent extends HttpEvent {

    private $controller;

    public function __construct(ServerRequestInterface $request, ResponseInterface $response, callable $controller) {
        parent::__construct($request, Engine::AFTER_CONTROLLER_EVENT);
        $this->controller = $controller;
        $this->setResponse($response);
    }

    public function getController() : callable {
        return $this->controller;
    }

}
