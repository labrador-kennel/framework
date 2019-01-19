<?php declare(strict_types=1);

namespace Cspray\Labrador\Http\Test;

use Auryn\Injector;
use Cspray\Labrador\AsyncEvent\Emitter;
use Cspray\Labrador\AsyncEvent\AmpEmitter;
use Cspray\Labrador\AsyncEvent\StandardEvent;
use Cspray\Labrador\CoreEngine;
use Cspray\Labrador\Engine;
use Cspray\Labrador\Http\DependencyGraph;
use Cspray\Labrador\Http\Plugin\RouterPlugin;
use Cspray\Labrador\Http\Router\FastRouteRouter;
use Cspray\Labrador\Http\Router\Router;
use Cspray\Labrador\PluginManager;

class DependencyGraphTest extends AsyncTestCase {

    public function testServicesRegisteredCorrectly() {
        $subject = new DependencyGraph();
        $injector = $subject->wireObjectGraph();

        $emitter = $injector->make(Emitter::class);
        $this->assertInstanceOf(AmpEmitter::class, $emitter);

        $engine = $injector->make(Engine::class);
        $this->assertInstanceOf(CoreEngine::class, $engine);

        $router = $injector->make(Router::class);
        $this->assertInstanceOf(FastRouteRouter::class, $router);
    }

    public function testPluginManagerInjectorShared() {
        $subject = new DependencyGraph();
        $injector = $subject->wireObjectGraph();
        $pluginManager = $injector->make(PluginManager::class);
        // normally we would not test private property accessors this way but it saves us from having to share the
        // Aury\Injector with itself and promoting the use of a service locator pattern.
        $reflectedPluginManager = new \ReflectionClass($pluginManager);
        $reflectedInjector = $reflectedPluginManager->getProperty('injector');
        $reflectedInjector->setAccessible(true);
        
        $this->assertSame($injector, $reflectedInjector->getValue($pluginManager));
    }

    public function testEngineLoadsRouterPlugin() {
        $subject = new DependencyGraph();
        $injector = $subject->wireObjectGraph();
        /** @var Engine $engine */
        $engine = $injector->make(Engine::class);
        $router = $injector->make(Router::class);
        $routerPlugin = $this->createMock(RouterPlugin::class);
        $routerPlugin->expects($this->once())
                     ->method('registerRoutes')
                     ->with($router);

        $engine->registerPlugin($routerPlugin);

        $emitter = $injector->make(Emitter::class);
        yield $emitter->emit(new StandardEvent(Engine::ENGINE_BOOTUP_EVENT, $engine));
    }

}