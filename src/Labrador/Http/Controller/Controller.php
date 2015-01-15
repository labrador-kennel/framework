<?php

/**
 * 
 * @license See LICENSE in source root
 * @version 1.0
 * @since   1.0
 */

namespace Labrador\Http\Controller;

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

} 
