<?php

declare(strict_types = 1);

/**
 * @license See LICENSE file in project root
 */

namespace Cspray\Labrador\Http;

use Auryn\Injector;
use Cspray\Labrador\Http\Controller\Controller;
use Cspray\Labrador\Http\Event\AfterControllerEvent;
use Cspray\Labrador\Http\Event\BeforeControllerEvent;
use Cspray\Labrador\Plugin\EventAwarePlugin;
use Cspray\Labrador\Plugin\ServiceAwarePlugin;
use Cspray\Labrador\Plugin\PluginDependentPlugin;
use League\Event\EmitterInterface;

class ControllerServicePlugin implements EventAwarePlugin, ServiceAwarePlugin, PluginDependentPlugin {

    private $activeController;
    private $controllers;
    private $dependsOn;

    public function __construct(array $controllers, array $dependsOn = []) {
        $this->controllers = $controllers;
        $this->dependsOn = $dependsOn;
    }

    /**
     * Perform any actions that should be completed by your Plugin before the
     * primary execution of your app is kicked off.
     */
    public function boot() {
        // noop
    }

    /**
     * Register the event listeners your Plugin responds to.
     *
     * @param EmitterInterface $emitter
     * @return void
     */
    public function registerEventListeners(EmitterInterface $emitter) {
        $cb = function(BeforeControllerEvent $event) {
            if ($this->activeController instanceof Controller) {
                $this->activeController->beforeController($event);
            }
        };
        $cb = $cb->bindTo($this);
        $emitter->addListener(Engine::BEFORE_CONTROLLER_EVENT, $cb);

        $cb = function(AfterControllerEvent $event) {
            if ($this->activeController instanceof Controller) {
                $this->activeController->afterController($event);
            }
        };
        $cb = $cb->bindTo($this);
        $emitter->addListener(Engine::AFTER_CONTROLLER_EVENT, $cb);
    }

    /**
     * Return an array of plugin names that this plugin depends on.
     *
     * @return array
     */
    public function dependsOn() : array {
        return $this->dependsOn;
    }

    /**
     * Register any services that the Plugin provides.
     *
     * @param Injector $injector
     * @return void
     */
    // TODO make this not be so crappy
    public function registerServices(Injector $injector) {
        foreach ($this->controllers as $controller) {
            $injector->share($controller);
            $cb = function($controller) {
                $this->activeController = $controller;
            };
            $cb = $cb->bindTo($this);
            $injector->prepare($controller, $cb);
        }
    }

}