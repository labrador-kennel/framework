<?php

declare(strict_types = 1);

/**
 * @license See LICENSE file in project root
 */

namespace Cspray\Labrador\Http\Event;

use Cspray\Labrador\Http\Engine;
use Cspray\Labrador\Event\EventFactory;
use League\Event\EventInterface;
use Symfony\Component\HttpFoundation\Request;

class HttpEventFactory implements EventFactory {

    private $eventClassMap = [
        Engine::ENVIRONMENT_INITIALIZE_EVENT => EnvironmentInitializeEvent::class,
        Engine::APP_EXECUTE_EVENT => AppExecuteEvent::class,
        Engine::APP_CLEANUP_EVENT => AppCleanupEvent::class,
        Engine::EXCEPTION_THROWN_EVENT => ExceptionThrownEvent::class,
        Engine::BEFORE_CONTROLLER_EVENT => BeforeControllerEvent::class,
        Engine::AFTER_CONTROLLER_EVENT => AfterControllerEvent::class
    ];

    private $request;

    public function __construct(Request $request) {
        $this->request = $request;
    }

    public function create(string $eventName, ...$args) : EventInterface {
        array_unshift($args, $this->request);

        $r = new \ReflectionClass($this->eventClassMap[$eventName]);
        return $r->newInstanceArgs($args);
    }
}