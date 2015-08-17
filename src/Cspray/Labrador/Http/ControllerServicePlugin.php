<?php

declare(strict_types = 1);

/**
 * @license See LICENSE file in project root
 */

namespace Cspray\Labrador\Http;

use Auryn\Injector;
use Cspray\Labrador\Plugin\EventAwarePlugin;
use Cspray\Labrador\Plugin\ServiceAwarePlugin;
use Cspray\Labrador\Plugin\PluginDependentPlugin;
use Evenement\EventEmitterInterface;

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
     * @param EventEmitterInterface $emitter
     * @return void
     */
    public function registerEventListeners(EventEmitterInterface $emitter) {
        // TODO: Implement registerEventListeners() method.
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
    public function registerServices(Injector $injector) {
        // TODO: Implement registerServices() method.
    }

    public function getActiveController() {
        return $this->activeController;
    }

}