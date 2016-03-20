<?php

declare(strict_types=1);

/**
 * A HandlerResolver that will match a handler that is a callable.
 *
 * @license See LICENSE in source root
 */

namespace Cspray\Labrador\Http\HandlerResolver;

use Psr\Http\Message\ServerRequestInterface;

class CallableResolver implements HandlerResolver {

    /**
     * @param ServerRequestInterface $request
     * @param mixed $handler
     * @return callable|false
     */
    public function resolve(ServerRequestInterface $request, $handler) {
        if (is_callable($handler)) {
            return $handler;
        }

        return false;
    }

}
