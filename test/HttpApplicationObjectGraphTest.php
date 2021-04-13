<?php declare(strict_types=1);

namespace Cspray\Labrador\Http\Test;

use Amp\PHPUnit\AsyncTestCase;
use Cspray\Labrador\Application;
use Cspray\Labrador\DotAccessSettings;
use Cspray\Labrador\Environment;
use Cspray\Labrador\EnvironmentType;
use Cspray\Labrador\Exception\InvalidStateException;
use Cspray\Labrador\Http\DefaultHttpApplication;
use Cspray\Labrador\Http\HttpApplication;
use Cspray\Labrador\Http\HttpApplicationObjectGraph;
use Cspray\Labrador\Http\Router\FastRouteRouter;
use Cspray\Labrador\Http\Router\Router;
use Cspray\Labrador\Http\Test\Stub\TestRouterPlugin;
use Cspray\Labrador\Settings;
use Cspray\Labrador\SettingsLoader;
use Cspray\Labrador\StandardEnvironment;
use Generator;
use Psr\Log\NullLogger;

class HttpApplicationObjectGraphTest extends AsyncTestCase {

    public function testCreateRouterReturnsSameInstance() {
        $injector = $this->getGoodInjector();
        $one = $injector->make(Router::class);
        $two = $injector->make(Router::class);

        $this->assertSame($one, $two);
    }

    public function testCreateRouterReturnsFastRouteRouter() {
        $injector = $this->getGoodInjector();
        $router = $injector->make(Router::class);
        $this->assertInstanceOf(FastRouteRouter::class, $router);
    }

    public function testCreateApplicationReturnsDefaultHttpApplication() {
        $injector = $this->getGoodInjector();
        $app = $injector->make(Application::class);
        $this->assertInstanceOf(DefaultHttpApplication::class, $app);
    }

    public function testCreateApplicationReturnsSameInstance() {
        $injector = $this->getGoodInjector();
        $one = $injector->make(Application::class);
        $two = $injector->make(Application::class);
        $this->assertSame($one, $two);
    }

    public function testCreateHttpApplicationReturnsDefaultHttpApplication() {
        $injector = $this->getGoodInjector();
        $app = $injector->make(HttpApplication::class);
        $this->assertInstanceOf(DefaultHttpApplication::class, $app);
    }

    public function testCreateHttpApplicationReturnsSameInstance() {
        $injector = $this->getGoodInjector();
        $one = $injector->make(HttpApplication::class);
        $two = $injector->make(HttpApplication::class);
        $this->assertSame($one, $two);
    }

    public function testRouterPluginsAreRegistered() : Generator {
        $injector = $this->getGoodInjector();
        $app = $injector->make(Application::class);
        $app->registerPlugin(TestRouterPlugin::class);
        yield $app->loadPlugins();
        $plugin = $app->getLoadedPlugin(TestRouterPlugin::class);

        $this->assertSame($plugin->router, $injector->make(Router::class));
    }

    public function testBadSettingsThrowsException() {
        $this->expectException(InvalidStateException::class);
        $this->expectExceptionMessage('The ' . HttpApplicationObjectGraph::class . ' requires that a Setting be' .
            ' available at "labrador.http.listenAddress" storing the address the socket should listen on.');

        $this->getBadInjector();
    }

    private function getGoodInjector() {
        return (new HttpApplicationObjectGraph(
            new StandardEnvironment(EnvironmentType::Test()),
            new NullLogger(),
            $this->getGoodSettingsLoader()
        ))->wireObjectGraph();
    }

    private function getBadInjector() {
        return (new HttpApplicationObjectGraph(
            new StandardEnvironment(EnvironmentType::Test()),
            new NullLogger(),
            $this->getBadSettingsLoader()
        ))->wireObjectGraph();
    }

    private function getGoodSettingsLoader() {
        return new class implements SettingsLoader {

            public function loadSettings(Environment $environment) : Settings {
                return new DotAccessSettings([
                    'labrador' => [
                        'http' => [
                            'listenAddress' => '127.0.0.1:0'
                        ]
                    ]
                ]);
            }
        };
    }

    private function getBadSettingsLoader() {
        return new class implements SettingsLoader {
            public function loadSettings(Environment $environment) : Settings {
                return new DotAccessSettings([]);
            }
        };
    }
}
