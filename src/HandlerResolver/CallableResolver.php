<?php

declare(strict_types=1);

/**
 * A HandlerResolver that will match a handler that is a callable.
 *
 * @license See LICENSE in source root
 */

namespace Cspray\Labrador\Http\HandlerResolver;

use Symfony\Component\HttpFoundation\Request;

class CallableResolver implements HandlerResolver {

    /**
     * @param mixed $handler
     * @return callable|false
     */
    public function resolve(Request $request, $handler) {
        if (is_callable($handler)) {
            return $handler;
        }

        return false;
    }

}
