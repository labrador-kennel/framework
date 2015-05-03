<?php

/**
 * A Labrador\Http plugin that ensures the beforeController and afterController
 * methods on Labrador\Http\Controller\Controller instances are properly invoked
 * during the appropriate event.
 */

namespace Labrador\Http\Controller;

use Evenement\EventEmitterInterface;
use Labrador\Http\Engine;
use Labrador\Http\Event\AfterControllerEvent;
use Labrador\Http\Event\BeforeControllerEvent;
use Labrador\Plugin\EventAwarePlugin;

class EventTriggeringPlugin implements EventAwarePlugin {

    public function registerEventListeners(EventEmitterInterface $emitter) {
        $controllerHasCallback = function($controller) {
            return is_array($controller) && $controller[0] instanceof Controller;
        };

        $emitter->on(Engine::BEFORE_CONTROLLER_EVENT, function(BeforeControllerEvent $event) use($controllerHasCallback) {
            $controller = $event->getController();
            if ($controllerHasCallback($controller)) {
                $controller[0]->beforeController($event);
            }
        });

        $emitter->on(Engine::AFTER_CONTROLLER_EVENT, function(AfterControllerEvent $event) use($controllerHasCallback) {
            $controller = $event->getController();
            if ($controllerHasCallback($controller)) {
                $controller[0]->afterController($event);
            }

        });
    }

    /**
     * Return the name of the plugin; this name should match /[A-Za-z0-9\.\-\_]/
     *
     * @return string
     */
    public function getName() {
        return 'controller.event-triggerer';
    }

    /**
     * Perform any actions that should be
     */
    public function boot() {

    }
}