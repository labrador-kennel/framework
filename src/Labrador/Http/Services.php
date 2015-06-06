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
use Labrador\Http\Controller\EventTriggeringPlugin;
use Auryn\Injector;
use FastRoute\RouteParser\Std as StdRouteParser;
use FastRoute\RouteCollector;
use FastRoute\DataGenerator\GroupCountBased as GcbGenerator;
use FastRoute\Dispatcher\GroupCountBased as GcbDispatcher;
use Evenement\EventEmitter;
use Evenement\EventEmitterInterface;
use Whoops\Handler\PrettyPageHandler;
use Whoops\Run;

class Services {

    public function createInjector() {
        $injector = new Injector();
        $this->wireObjectGraph($injector);
        $this->registerCorePlugins($injector->make(Engine::class), $injector);

        return $injector;
    }

    private function wireObjectGraph(Injector $injector) {
        $injector->share($injector);

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

        $injector->share(Run::class);
        $injector->prepare(Run::class, function(Run $run) {
            $run->pushHandler(new PrettyPageHandler());
        });

        $injector->share(ExceptionHandlingPlugin::class);
        $injector->share(EventTriggeringPlugin::class);
    }

    private function registerCorePlugins(Engine $engine, Injector $injector) {
        $defaultExceptionHandler = $injector->make(ExceptionHandlingPlugin::class);
        $controllerEventTrigger = $injector->make(EventTriggeringPlugin::class);

        $engine->registerPlugin($defaultExceptionHandler);
        $engine->registerPlugin($controllerEventTrigger);
    }

} 
