<?php

declare(strict_types = 1);

/**
 * @license See LICENSE file in project root
 */

namespace Cspray\Labrador\Http\Event;

use Cspray\Labrador\Event\AppExecuteEvent as LabradorAppExecuteEvent;
use Symfony\Component\HttpFoundation\Request;

class AppExecuteEvent extends LabradorAppExecuteEvent {

    private $request;

    public function __construct(Request $request) {
        parent::__construct();
        $this->request = $request;
    }

    public function getRequest() : Request {
        return $this->request;
    }

}