<?php

/**
 * 
 * @license See LICENSE in source root
 * @version 1.0
 * @since   1.0
 */

namespace Cspray\Labrador\Http;

use Cspray\Labrador\EnvironmentIntegrationConfig;
use Cspray\Labrador\PluginManager;
use Cspray\Labrador\Event\EnvironmentInitializeEvent;
use Cspray\Labrador\Http\Router;
use Cspray\Labrador\Http\Controller\EventTriggeringPlugin;
use Auryn\Injector;
use FastRoute\RouteParser\Std as StdRouteParser;
use FastRoute\RouteCollector;
use FastRoute\DataGenerator\GroupCountBased as GcbGenerator;
use FastRoute\Dispatcher\GroupCountBased as GcbDispatcher;
use Evenement\EventEmitter;
use Evenement\EventEmitterInterface;
use Telluris\Environment;
use Telluris\Config\Storage;
use Whoops\Handler\PrettyPageHandler;
use Whoops\Run;

class Services {

    private $envConfig;

    public function __construct(EnvironmentIntegrationConfig $envConfig = null) {
        $this->envConfig = $envConfig ?? new EnvironmentIntegrationConfig();
    }

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

        $injector->share(Environment::class);
        $injector->define(Environment::class, [':env' => $this->envConfig->getEnv()]);
        $envStorage = $this->envConfig->getStorage();
        $injector->share($envStorage);
        $injector->alias(Storage::class, get_class($envStorage));
        if ($this->envConfig->runInitializers()) {
            /** @var EventEmitterInterface $emitter */
            $emitter = $injector->make(EventEmitterInterface::class);
            $emitter->on(Engine::ENVIRONMENT_INITIALIZE_EVENT, function(EnvironmentInitializeEvent $event) {
                $event->getEnvironment()->runInitializers();
            });
        }

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
