<?php

declare(strict_types = 1);

/**
 * @license See LICENSE file in project root
 */

namespace Cspray\Labrador\Http;

use Cspray\Labrador\Engine;
use Cspray\Labrador\Event\ExceptionThrownEvent;
use Cspray\Labrador\Http\Services as HttpServices;
use Cspray\Labrador\EnvironmentIntegrationConfig;
use Auryn\Injector;
use Whoops\Run;

function bootstrap(EnvironmentIntegrationConfig $config = null) : Injector {
    $injector = (new HttpServices($config))->createInjector();

    $run = $injector->make(Run::class);

    $run->register();
    $engine = $injector->make(Engine::class);

    $engine->onExceptionThrown(function(ExceptionThrownEvent $event) use($run) {
        $run->handleException($event->getException());
    });

    return $injector;
}