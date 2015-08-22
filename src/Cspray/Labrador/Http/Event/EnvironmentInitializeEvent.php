<?php

declare(strict_types = 1);

/**
 * @license See LICENSE file in project root
 */

namespace Cspray\Labrador\Http\Event;

use Cspray\Labrador\Event\EnvironmentInitializeEvent as LabradorEnvInitEvent;
use Symfony\Component\HttpFoundation\Request;
use Cspray\Telluris\Environment;

class EnvironmentInitializeEvent extends LabradorEnvInitEvent {

    private $request;

    public function __construct(Request $request, Environment $environment) {
        parent::__construct($environment);
        $this->request = $request;
    }

    public function getRequest() : Request {
        return $this->request;
    }

}