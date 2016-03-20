<?php

declare(strict_types = 1);

/**
 * @license See LICENSE file in project root
 */

namespace Cspray\Labrador\Http;

use Psr\Http\Message\ResponseInterface;

interface ResponseDeliverer {

    public function deliver(ResponseInterface $response);

}