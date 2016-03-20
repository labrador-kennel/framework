<?php

declare(strict_types=1);

/**
 * Convert a pice of data, or a handler, into a callable function.
 *
 * @license See LICENSE in source root
 */

namespace Cspray\Labrador\Http\HandlerResolver;

use Psr\Http\Message\ServerRequestInterface;

interface HandlerResolver {

    /**
     * If the implementation cannot turn $handler into a callable type return false.
     *
     * @param ServerRequestInterface $request
     * @param mixed $handler
     * @return callable|false
     * @throws \Cspray\Labrador\Http\Exception\InvalidHandlerException
     */
    public function resolve(ServerRequestInterface $request, $handler);

}
