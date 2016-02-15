<?php

declare(strict_types=1);

/**
 * Convert a pice of data, or a handler, into a callable function.
 *
 * @license See LICENSE in source root
 */

namespace Cspray\Labrador\Http\HandlerResolver;

use Symfony\Component\HttpFoundation\Request;

interface HandlerResolver {

    /**
     * If the implementation cannot turn $handler into a callable type return false.
     *
     * @param Request $request
     * @param mixed $handler
     * @return callable|false
     * @throws \Cspray\Labrador\Http\Exception\InvalidHandlerException
     */
    public function resolve(Request $request, $handler);

}
