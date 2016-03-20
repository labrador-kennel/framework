<?php

declare(strict_types=1);

/**
 * @license See LICENSE in source root
 */

namespace Cspray\Labrador\Http\Event;

use League\Event\Event;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

abstract class HttpEvent extends Event {

    private $request;
    private $response;

    public function __construct(ServerRequestInterface $request, string $name) {
        parent::__construct($name);
    }

    public function getRequest() : ServerRequestInterface {
        return $this->request;
    }

    public function setResponse(ResponseInterface $response) {
        $this->response = $response;
    }

    public function getResponse() {
        return $this->response;
    }

}
