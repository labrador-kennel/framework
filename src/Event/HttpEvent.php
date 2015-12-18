<?php

declare(strict_types=1);

/**
 * @license See LICENSE in source root
 */

namespace Cspray\Labrador\Http\Event;

use League\Event\Event;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

abstract class HttpEvent extends Event {

    private $request;
    private $response;

    public function __construct(Request $request, string $name) {
        parent::__construct($name);
        $this->request = $request;
    }

    /**
     * @return Request
     */
    public function getRequest() : Request {
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
