<?php

declare(strict_types=1);

/**
 * Router that will take a handler that is an instanceof Response and resolve to
 * a closure that will return the Response.
 *
 * @license See LICENSE in source root
 */

namespace Cspray\Labrador\Http\HandlerResolver;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class ResponseResolver implements HandlerResolver {

    /**
     * @param mixed $handler
     * @return callable|false
     */
    function resolve(Request $request, $handler) {
        if ($handler instanceof Response) {
            return function() use($handler) {
                return $handler;
            };
        }

        return false;
    }

}
