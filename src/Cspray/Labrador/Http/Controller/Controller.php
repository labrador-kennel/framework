<?php

declare(strict_types=1);

/**
 * @license See LICENSE in source root
 */

namespace Cspray\Labrador\Http\Controller;

use Cspray\Labrador\Http\Event;

abstract class Controller {

    private $data = [];

    public function getAll() : array {
        return $this->data;
    }

    public function get(string $key, $default = null) {
        return isset($this->data[$key]) ? $this->data[$key] : $default;
    }

    public function set(string $key, $val) : self {
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
