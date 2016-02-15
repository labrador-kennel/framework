<?php

declare(strict_types = 1);

/**
 * @license See LICENSE file in project root
 */

namespace Cspray\Labrador\Http;

use Cspray\Labrador\Engine as LabradorEngine;
use Cspray\Labrador\Event\ExceptionThrownEvent;
use Cspray\Labrador\Http\Services as HttpServices;
use Auryn\Injector;
use Whoops\Run;

function bootstrap() : Injector {
    $injector = (new HttpServices())->wireObjectGraph();

    $run = $injector->make(Run::class);

    $run->register();
    $engine = $injector->make(LabradorEngine::class);

    $engine->onExceptionThrown(function(ExceptionThrownEvent $event) use($run) {
        $run->handleException($event->getException());
    });

    return $injector;
}