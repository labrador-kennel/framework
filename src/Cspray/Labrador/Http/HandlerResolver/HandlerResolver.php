<?php

declare(strict_types=1);

/**
 * Convert a pice of data, or a handler, into a callable function.
 * 
 * @license See LICENSE in source root
 */

namespace Cspray\Labrador\Http\HandlerResolver;

interface HandlerResolver {

    /**
     * If the implementation cannot turn $handler into a callable type return false.
     *
     * @param mixed $handler
     * @return callable|false
     * @throws \Cspray\Labrador\Http\Exception\InvalidHandlerException
     */
    function resolve($handler);

} 
