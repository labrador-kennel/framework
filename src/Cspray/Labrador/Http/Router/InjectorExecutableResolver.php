<?php

declare(strict_types = 1);

/**
 * @license See LICENSE file in project root
 */

namespace Cspray\Labrador\Http\Router;

use Auryn\Injector;

class InjectorExecutableResolver implements HandlerResolver {

    private $resolver;
    private $injector;

    public function __construct(HandlerResolver $resolver, Injector $injector) {
        $this->resolver = $resolver;
        $this->injector = $injector;
    }

    /**
     * If the implementation cannot turn $handler into a callable type return false.
     *
     * @param mixed $handler
     * @return callable|false
     * @throws \Cspray\Labrador\Http\Exception\InvalidHandlerException
     */
    function resolve($handler) {
        $cb = $this->resolver->resolve($handler);
        if ($cb) {
            $injector = $this->injector;
            return function() use($cb, $injector) {
                return $injector->execute($cb);
            };
        }

        return false;
    }
}