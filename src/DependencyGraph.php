<?php

/**
 *
 * @license See LICENSE in source root
 * @version 1.0
 * @since   1.0
 */

namespace Cspray\Labrador\Http;

use Cspray\Labrador\{AsyncEvent\AmpEmitter,
    AsyncEvent\Emitter,
    CoreEngine,
    Engine,
    Http\Plugin\RouterPlugin,
    PluginManager};
use Cspray\Labrador\Http\Router;
use Auryn\Injector;
use FastRoute\RouteParser\Std as StdRouteParser;
use FastRoute\RouteCollector;
use FastRoute\DataGenerator\GroupCountBased as GcbGenerator;
use FastRoute\Dispatcher\GroupCountBased as GcbDispatcher;

class DependencyGraph {

    public function wireObjectGraph(Injector $injector = null) : Injector {
        $injector = $injector ?? new Injector();

        $this->registerCoreServices($injector);
        $this->registerRouterServices($injector);

        return $injector;
    }

    private function registerCoreServices(Injector $injector) {
        $injector->define(PluginManager::class, [
            ':injector' => $injector
        ]);

        $injector->share(Emitter::class);
        $injector->alias(Emitter::class, AmpEmitter::class);

        $injector->share(Engine::class);
        $injector->alias(Engine::class, CoreEngine::class);
        $injector->prepare(Engine::class, function(Engine $engine, Injector $injector) {
            $injector->execute([$engine, 'registerPluginHandler'], [
                RouterPlugin::class,
                function(RouterPlugin $plugin) use($injector) {
                    $injector->execute([$plugin, 'registerRoutes']);
                }
            ]);
        });
    }

    private function registerRouterServices(Injector $injector) {
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
    }

}
