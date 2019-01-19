<?php

declare(strict_types=1);

/**
 * @license See LICENSE in source root
 */

namespace Cspray\Labrador\Http\Controller;

use Amp\Http\Server\RequestHandler;

interface Controller extends RequestHandler {

    public function toString() : string;

} 
