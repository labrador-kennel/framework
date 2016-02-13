<?php

declare(strict_types = 1);

/**
 * A HandlerResolver that will decorate any callable to be invoked by using
 * Auryn\Injector::execute; this allows your handler callables to declare
 * necessary dependencies and they will be provisioned for you.
 *
 * @license See LICENSE file in project root
 */

namespace Cspray\Labrador\Http\HandlerResolver;

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

    // TODO figure out best way to get Request in here
    function resolve($handler) {
        $cb = $this->resolver->resolve($handler);
        if ($cb) {
            $injector = $this->injector;
            return function() use($cb, $injector) {
                return $injector->execute($cb);  // TODO add args with request to this call
            };
        }

        return false;
    }
}