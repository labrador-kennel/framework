<?php

declare(strict_types=1);

/**
 * @license See LICENSE in source root
 */

namespace Labrador\Http\Controller;

use Amp\Http\Server\RequestHandler;
use Cspray\AnnotatedContainer\Attribute\Service;

#[Service]
interface Controller extends RequestHandler {

    public function toString() : string;
}
