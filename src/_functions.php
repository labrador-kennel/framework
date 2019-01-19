<?php

declare(strict_types = 1);

/**
 * @license See LICENSE file in project root
 */

namespace Cspray\Labrador\Http;

use Auryn\Injector;

function bootstrap() : Injector {
    return new Injector();
}