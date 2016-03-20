<?php

declare(strict_types=1);

/**
 * Router that will take a handler that is an instanceof Response and resolve to
 * a closure that will return the Response.
 *
 * @license See LICENSE in source root
 */

namespace Cspray\Labrador\Http\HandlerResolver;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class ResponseResolver implements HandlerResolver {

    /**
     * @param mixed $handler
     * @return callable|false
     */
    public function resolve(ServerRequestInterface $request, $handler) {
        if ($handler instanceof ResponseInterface) {
            return function() use($handler) {
                return $handler;
            };
        }
        return false;
    }

}
