<?php

/**
 * Should convert a routed handler into an appropriate callable function.
 * 
 * @license See LICENSE in source root
 */

namespace Labrador\Http;

interface HandlerResolver {

    /**
     * If the implementation cannot turn $handler into a callable type return false.
     *
     * @param mixed $handler
     * @return callable|false
     * @throws \Labrador\Http\Exception\InvalidHandlerException
     */
    function resolve($handler);

} 
