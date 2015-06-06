<?php

/**
 * 
 * @license See LICENSE in source root
 * @version 1.0
 * @since   1.0
 */

namespace Labrador\Http\Event;

use Labrador\Event\Event;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

abstract class HttpEvent extends Event {

    private $request;
    private $response;

    public function __construct(Request $request, $name) {
        parent::__construct($name);
        $this->request = $request;
    }

    /**
     * @return Request
     */
    public function getRequest() {
        return $this->request;
    }

    /**
     * @return Response|null
     */
    public function getResponse() {
        return $this->response;
    }

    public function setResponse(Response $response) {
        $this->response = $response;
    }

} 
