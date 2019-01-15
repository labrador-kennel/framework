<?php

declare(strict_types=1);

/**
 * @license See LICENSE in source root
 */

namespace Cspray\Labrador\Http\Controller;

use Amp\Http\Server\RequestHandler;
use Amp\Promise;

interface Controller extends RequestHandler {

    public function beforeAction() : Promise;

    public function afterAction() : Promise;

} 
