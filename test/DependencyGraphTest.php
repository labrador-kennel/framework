<?php declare(strict_types=1);

namespace Cspray\Labrador\Http\Test;

use Amp\PHPUnit\AsyncTestCase;
use Amp\Socket\Server;
use Cspray\Labrador\AmpEngine;
use Cspray\Labrador\Application;
use Cspray\Labrador\AsyncEvent\Emitter;
use Cspray\Labrador\AsyncEvent\AmpEmitter;
use Cspray\Labrador\Configuration;
use Cspray\Labrador\Engine;
use Cspray\Labrador\Http\DependencyGraph;
use Cspray\Labrador\Http\Test\Stub\TestRouterPlugin;
use Cspray\Labrador\Http\Router\FastRouteRouter;
use Cspray\Labrador\Http\Router\Router;
use Cspray\Labrador\Plugin\PluginManager;
use Cspray\Labrador\DependencyGraph as CoreDependencyGraph;

class DependencyGraphTest extends AsyncTestCase {

    private $configuration;

    public function setUp() : void {
        parent::setUp();
        $this->configuration = $this->getMockBuilder(Configuration::class)->getMock();
        $this->configuration->expects($this->once())->method('getLogPath')->willReturn('/dev/null');
    }

    public function testServicesRegisteredCorrectly() {
        $subject = new DependencyGraph(new CoreDependencyGraph($this->configuration));
        $injector = $subject->wireObjectGraph();

        $emitter = $injector->make(Emitter::class);
        $this->assertInstanceOf(AmpEmitter::class, $emitter);

        $engine = $injector->make(Engine::class);
        $this->assertInstanceOf(AmpEngine::class, $engine);

        $router = $injector->make(Router::class);
        $this->assertInstanceOf(FastRouteRouter::class, $router);
    }

    public function testPluginManagerInjectorShared() {
        $subject = new DependencyGraph(new CoreDependencyGraph($this->configuration));
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
        $subject = new DependencyGraph(new CoreDependencyGraph($this->configuration));
        $injector = $subject->wireObjectGraph();
        /** @var Application $app */
        $app = $injector->make(Application::class, [':socketServers' => new Server(@fopen('/dev/null', 'rb'))]);

        $app->registerPlugin(TestRouterPlugin::class);
        yield $app->loadPlugins();

        $router = $injector->make(Router::class);
        $this->assertSame($app->getLoadedPlugin(TestRouterPlugin::class)->router, $router);
    }
}
