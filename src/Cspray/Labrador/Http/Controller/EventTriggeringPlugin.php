<?php

declare(strict_types=1);

/**
 * A Labrador\Http plugin that ensures the beforeController and afterController
 * methods on Labrador\Http\Controller\Controller instances are properly invoked
 * during the appropriate event.
 *
 * This plugin comes registered for the Http\Engine created by the Auryn\Injector
 * from Http\Services.
 *
 * @license See LICENSE in source root
 */

namespace Cspray\Labrador\Http\Controller;

use Cspray\Labrador\Http\Engine;
use Cspray\Labrador\Http\Event\AfterControllerEvent;
use Cspray\Labrador\Http\Event\BeforeControllerEvent;
use Cspray\Labrador\Plugin\EventAwarePlugin;
use Evenement\EventEmitterInterface;

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
    public function getName() : string {
        return 'controller.event-triggerer';
    }

    /**
     * Perform any actions that should be
     */
    public function boot() {
        // noop
    }
}