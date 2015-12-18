<?php

declare(strict_types = 1);

/**
 * @license See LICENSE file in project root
 */

namespace Cspray\Labrador\Http\Event;

use Cspray\Labrador\Event\AppCleanupEvent as LabradorAppCleanupEvent;
use Symfony\Component\HttpFoundation\Request;

class AppCleanupEvent extends LabradorAppCleanupEvent {

    private $request;

    public function __construct(Request $request) {
        parent::__construct();
        $this->request = $request;
    }

    public function getRequest() : Request {
        return $this->request;
    }

}