<?php

/**
 * 
 * @license See LICENSE in source root
 * @version 1.0
 * @since   1.0
 */

namespace Labrador\Http;

use Labrador\Plugin\PluginManager;
use Labrador\Http\Router;
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
        $injector->share(Router\FastRouteRouter::class);
        $injector->define(Router\FastRouteRouter::class, [
            'collector' => RouteCollector::class,
            ':dispatcherCb' => function(array $data) use($injector) {
                return $injector->make(GcbDispatcher::class, [':data' => $data]);
            }
        ]);
        $injector->alias(Router\Router::class, Router\FastRouteRouter::class);

        $injector->share(Router\ResolverChain::class);
        $injector->prepare(Router\ResolverChain::class, function(Router\ResolverChain $chain, Injector $injector) {
            $chain->add($injector->make(Router\ResponseResolver::class));
            $chain->add($injector->make(Router\CallableResolver::class));
            $chain->add($injector->make(Router\ControllerActionResolver::class));
        });
        $injector->alias(Router\HandlerResolver::class, Router\ResolverChain::class);

        $injector->share(EventEmitter::class);
        $injector->alias(EventEmitterInterface::class, EventEmitter::class);

        $injector->share(PluginManager::class);

        $injector->share(Engine::class);
    }

} 
