<?php

/**
 *
 * @license See LICENSE in source root
 * @version 1.0
 * @since   1.0
 */

namespace Cspray\Labrador\Http;

use Cspray\Labrador\{Engine, PluginManager};
use Cspray\Labrador\Http\{Engine as HttpEngine, Router};
use Cspray\Labrador\Http\Event\HttpEventFactory;
use Cspray\Labrador\Http\HandlerResolver\{
    CallableResolver,
    HandlerResolver,
    ResolverChain,
    ResponseResolver,
    ControllerActionResolver,
    InjectorExecutableResolver
};
use Auryn\Injector;
use FastRoute\RouteParser\Std as StdRouteParser;
use FastRoute\RouteCollector;
use FastRoute\DataGenerator\GroupCountBased as GcbGenerator;
use FastRoute\Dispatcher\GroupCountBased as GcbDispatcher;
use League\Event\{EmitterInterface, Emitter};
use Whoops\Handler\PrettyPageHandler;
use Whoops\Run;

class Services {

    public function createInjector() {
        $injector = new Injector();
        $this->wireObjectGraph($injector);

        return $injector;
    }

    private function wireObjectGraph(Injector $injector) {
        $injector->share($injector);
        $this->registerCoreLabradorServices($injector);
        $this->registerCoreHttpServices($injector);
        $this->registerCoreHttpPlugins($injector);
        $this->registerExceptionHandlerServices($injector);
    }

    private function registerCoreHttpServices(Injector $injector) {
        $injector->share(Engine::class);
        $injector->alias(Engine::class, HttpEngine::class);
        $injector->share(HttpEventFactory::class);
        $injector->share(Request::createFromGlobals());
        $this->registerRouterServices($injector);
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

        $injector->share(ResolverChain::class);
        $injector->prepare(ResolverChain::class, function(ResolverChain $chain, Injector $injector) {
            $chain->add($injector->make(ResponseResolver::class));
            $chain->add($injector->make(CallableResolver::class));
            $chain->add($injector->make(ControllerActionResolver::class));
        });

        $injector->share(InjectorExecutableResolver::class);
        $injector->define(InjectorExecutableResolver::class, ['resolver' => ResolverChain::class]);
        $injector->alias(HandlerResolver::class, InjectorExecutableResolver::class);
    }

    private function registerCoreLabradorServices(Injector $injector) {
        $injector->share(Emitter::class);
        $injector->alias(EmitterInterface::class, Emitter::class);

        $injector->share(PluginManager::class);
    }

    private function registerExceptionHandlerServices(Injector $injector) {
        $injector->share(Run::class);
        $injector->prepare(Run::class, function(Run $run) {
            $run->pushHandler(new PrettyPageHandler());
        });
        $injector->share(ExceptionHandlingPlugin::class);
    }

    private function registerCoreHttpPlugins(Injector $injector) {
        $engine = $injector->make(Engine::class);
        $defaultExceptionHandler = $injector->make(ExceptionHandlingPlugin::class);

        $engine->registerPlugin($defaultExceptionHandler);
    }

}
