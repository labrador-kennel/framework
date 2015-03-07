<?php

/**
 * 
 * @license See LICENSE in source root
 * @version 1.0
 * @since   1.0
 */

namespace Labrador\Http;

use Labrador\Plugin\PluginManager;
use Labrador\Http\Resolver\ResolverChain;
use Labrador\Http\Resolver\ResponseResolver;
use Labrador\Http\Resolver\CallableResolver;
use Labrador\Http\Resolver\ControllerActionResolver;
use Auryn\Injector;
use FastRoute\RouteParser\Std as StdRouteParser;
use FastRoute\RouteCollector;
use FastRoute\DataGenerator\GroupCountBased as GcbGenerator;
use FastRoute\Dispatcher\GroupCountBased as GcbDispatcher;
use Evenement\EventEmitter;
use Evenement\EventEmitterInterface;

class Services {

    public function register(Injector $injector) {
        $injector->share($injector);
        $injector->alias(Injector::class, get_class($injector));

        $injector->share(RouteCollector::class);
        $injector->define(RouteCollector::class, [
            'routeParser' => StdRouteParser::class,
            'dataGenerator' => GcbGenerator::class
        ]);
        $injector->share(FastRouteRouter::class);
        $injector->define(FastRouteRouter::class, [
            'collector' => RouteCollector::class,
            ':dispatcherCb' => function(array $data) use($injector) {
                return $injector->make(GcbDispatcher::class, [':data' => $data]);
            }
        ]);
        $injector->alias(Router::class, FastRouteRouter::class);

        $injector->share(ResolverChain::class);
        $injector->prepare(ResolverChain::class, function(ResolverChain $chain, Injector $injector) {
            $chain->add($injector->make(ResponseResolver::class));
            $chain->add($injector->make(CallableResolver::class));
            $chain->add($injector->make(ControllerActionResolver::class));
        });
        $injector->alias(HandlerResolver::class, ResolverChain::class);

        $injector->share(EventEmitter::class);
        $injector->alias(EventEmitterInterface::class, EventEmitter::class);

        $injector->share(PluginManager::class);

        $injector->share(Engine::class);
    }

} 
