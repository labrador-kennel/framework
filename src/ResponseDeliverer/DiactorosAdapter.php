<?php

declare(strict_types = 1);

/**
 * @license See LICENSE file in project root
 */

namespace Cspray\Labrador\Http\ResponseDeliverer;

use Cspray\Labrador\Http\ResponseDeliverer;
use Psr\Http\Message\ResponseInterface;
use Zend\Diactoros\Response\SapiEmitter;

class DiactorosAdapter implements ResponseDeliverer {

    private $diactorosEmitter;

    public function __construct() {
        $this->diactorosEmitter = new SapiEmitter();
    }

    public function deliver(ResponseInterface $response) {
        $this->diactorosEmitter->emit($response);
    }

}