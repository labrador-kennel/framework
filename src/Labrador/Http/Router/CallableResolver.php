<?php

/**
 * HandlerResolver implementation where if a $handler is a callable return it.
 * 
 * @license See LICENSE in source root
 */

namespace Labrador\Http\Router;

class CallableResolver implements HandlerResolver {

    /**
     * @param mixed $handler
     * @return callable|false
     */
    function resolve($handler) {
        if (is_callable($handler)) {
            return $handler;
        }

        return false;
    }

}
