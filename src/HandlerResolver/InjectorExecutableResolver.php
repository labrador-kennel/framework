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
use Symfony\Component\HttpFoundation\Request;

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
    function resolve(Request $request, $handler) {
        $cb = $this->resolver->resolve($request, $handler);
        if ($cb) {
            $injector = $this->injector;
            return function() use($cb, $injector, $request) {
                return $injector->execute($cb, [':request' => $request, ':req' => $request]);
            };
        }

        return false;
    }
}