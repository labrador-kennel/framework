<?php

declare(strict_types = 1);

/**
 * @license See LICENSE file in project root
 */

namespace Cspray\Labrador\Http\Event;

use Cspray\Labrador\Event\ExceptionThrownEvent as LabradorExceptionThrownEvent;
use Symfony\Component\HttpFoundation\Request;
use Exception as PhpException;

class ExceptionThrownEvent extends LabradorExceptionThrownEvent {

    private $request;

    public function __construct(Request $request, PhpException $exception) {
        parent::__construct($exception);
        $this->request = $request;
    }

    public function getRequest() : Request {
        return $this->request;
    }

}