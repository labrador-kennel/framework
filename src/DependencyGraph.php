<?php

namespace Cspray\Labrador\Http;

use Cspray\Labrador\Application;
use Cspray\Labrador\Http\Router;
use Cspray\Labrador\Http\Plugin\RouterPlugin;
use Cspray\Labrador\DependencyGraph as CoreDependencyGraph;

use Auryn\Injector;
use FastRoute\DataGenerator\GroupCountBased as GcbGenerator;
use FastRoute\Dispatcher\GroupCountBased as GcbDispatcher;
use FastRoute\RouteCollector;
use FastRoute\RouteParser\Std as StdRouteParser;

class DependencyGraph {

    private $coreDependencyGraph;

    public function __construct(CoreDependencyGraph $coreDependencyGraph) {
        $this->coreDependencyGraph = $coreDependencyGraph;
    }

    public function wireObjectGraph(Injector $injector = null) : Injector {
        $injector = $injector ?? new Injector();

        $this->registerCoreServices($injector);
        $this->registerRouterServices($injector);

        return $injector;
    }

    private function registerCoreServices(Injector $injector) {
        $this->coreDependencyGraph->wireObjectGraph($injector);
    }

    private function registerRouterServices(Injector $injector) {
        $injector->share(Application::class);
        $injector->alias(Application::class, HttpApplication::class);
        $injector->prepare(Application::class, function(Application $app, Injector $injector) {
            $injector->execute([$app, 'registerPluginLoadHandler'], [
                 RouterPlugin::class,
                function(RouterPlugin $plugin) use($injector) {
                    $injector->execute([$plugin, 'registerRoutes']);
                }
            ]);
        });
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
