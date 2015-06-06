<?php

/**
 * 
 * @license See LICENSE in source root
 * @version 1.0
 * @since   1.0
 */

namespace Labrador\Http\Controller;

use Labrador\Http\Event;

abstract class Controller {

    private $data = [];

    protected function getAll() {
        return $this->data;
    }

    protected function get($key, $default = null) {
        return isset($this->data[$key]) ? $this->data[$key] : $default;
    }

    protected function set($key, $val) {
        $this->data[$key] = $val;
        return $this;
    }

    /**
     * You can optionally override this method to perform an action before
     * the controller action is invoked.
     *
     * To rely on this behavior you must be sure that the
     * Controller\EventTriggeringPlugin has been appropriately registered
     * with the engine. If you create the Http\Engine with the Http\Services
     * this plugin comes pre-registered.
     *
     * @param Event\BeforeControllerEvent $event
     */
    public function beforeController(Event\BeforeControllerEvent $event) {
        // noop
    }

    /**
     * You can optionally override this method to perform an action after
     * the controller action is invoked.
     *
     * To rely on this behavior you must be sure that the
     * Controller\EventTriggeringPlugin has been appropriately registered
     * with the engine. If you create the Http\Engine with the Http\Services
     * this plugin comes pre-registered.
     *
     * @param Event\AfterControllerEvent $event
     */
    public function afterController(Event\AfterControllerEvent $event) {
        // noop
    }

} 
