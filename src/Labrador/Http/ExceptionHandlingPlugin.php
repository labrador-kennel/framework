<?php


namespace Labrador\Http;

use Evenement\EventEmitterInterface;
use Labrador\Event\ExceptionThrownEvent;
use Labrador\Plugin\EventAwarePlugin;
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
     * @param EventEmitterInterface $emitter
     */
    public function registerEventListeners(EventEmitterInterface $emitter) {
        $run = $this->whoopsRun;
        $emitter->on(Engine::EXCEPTION_THROWN_EVENT, function(ExceptionThrownEvent $event) use($run, $emitter) {
            if (count($emitter->listeners(Engine::EXCEPTION_THROWN_EVENT)) === 1) {
                $run->handleException($event->getException());
            }
        });
    }

    /**
     * Return the name of the plugin; this name should match /[A-Za-z0-9\.\-\_]/
     *
     * @return string
     */
    public function getName() {
        return 'labrador.http.exception-handler';
    }

    /**
     * Perform any actions that should be
     */
    public function boot() {

    }

}
