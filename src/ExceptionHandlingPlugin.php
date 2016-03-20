<?php

/**
 *
 */

namespace Cspray\Labrador\Http;

use Cspray\Labrador\Event\ExceptionThrownEvent;
use Cspray\Labrador\Plugin\EventAwarePlugin;
use League\Event\EmitterInterface;
use Whoops\Run;

class ExceptionHandlingPlugin implements EventAwarePlugin {

    private $whoopsRun;

    /**
     * @param Run $run
     */
    public function __construct(Run $run) {
        $this->whoopsRun = $run;
    }

    /**
     * @param EmitterInterface $emitter
     */
    public function registerEventListeners(EmitterInterface $emitter) {
        $run = $this->whoopsRun;
        $emitter->addListener(Engine::EXCEPTION_THROWN_EVENT, function(ExceptionThrownEvent $event) use($run, $emitter) {
            if (count($emitter->getListeners(Engine::EXCEPTION_THROWN_EVENT)) === 1) {
                $run->handleException($event->getException());
            }
        });
    }

}
