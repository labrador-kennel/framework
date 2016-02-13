<?php

declare(strict_types=1);

/**
 * Allows for a series of HandlerResolver to attempt to resolve a given $handler;
 * the first HandlerResolver in the chain that returns a callable wins.
 *
 * @license See LICENSE in source root
 */

namespace Cspray\Labrador\Http\HandlerResolver;

use Symfony\Component\HttpFoundation\Request;

class ResolverChain implements HandlerResolver {

    /**
     * @property HandlerResolver[]
     */
    private $resolvers = [];

    /**
     * @param mixed $handler
     * @return callable|false
     */
    function resolve(Request $request, $handler) {
        /** @var HandlerResolver $resolver */
        foreach ($this->resolvers as $resolver) {
            $cb = $resolver->resolve($request, $handler);
            if (is_callable($cb)) {
                return $cb;
            }
        }

        return false;
    }

    /**
     * @param HandlerResolver $resolver
     * @return $this
     */
    function add(HandlerResolver $resolver) : self {
        $this->resolvers[] = $resolver;
        return $this;
    }

}
