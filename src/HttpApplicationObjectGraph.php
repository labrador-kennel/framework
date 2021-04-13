<?php declare(strict_types=1);

namespace Cspray\Labrador\Http;

use Amp\Socket\Server;
use Auryn\Injector;
use Auryn\InjectorException;
use Cspray\Labrador\Application;
use Cspray\Labrador\AsyncEvent\EventEmitter;
use Cspray\Labrador\CoreApplicationObjectGraph;
use Cspray\Labrador\Environment;
use Cspray\Labrador\Exception\DependencyInjectionException;
use Cspray\Labrador\Exception\InvalidStateException;
use Cspray\Labrador\Http\Plugin\RouterPlugin;
use Cspray\Labrador\Http\Router\FastRouteRouter;
use Cspray\Labrador\Http\Router\Router;
use Cspray\Labrador\Plugin\Pluggable;
use Cspray\Labrador\Settings;
use Cspray\Labrador\SettingsLoader;
use FastRoute\RouteCollector;
use FastRoute\RouteParser;
use FastRoute\DataGenerator;
use FastRoute\Dispatcher;
use Psr\Log\LoggerInterface;

class HttpApplicationObjectGraph extends CoreApplicationObjectGraph {

    public function __construct(Environment $environment, LoggerInterface $logger, SettingsLoader $settingsLoader) {
        parent::__construct($environment, $logger, $settingsLoader);
    }

    /**
     * @throws DependencyInjectionException
     * @throws InvalidStateException
     */
    public function wireObjectGraph() : Injector {
        $injector = parent::wireObjectGraph();

        try {
            $settings = $injector->make(Settings::class);
            if (!$settings->has('labrador.http.listenAddress')) {
                $msg = sprintf(
                    'The %s requires that a Setting be available at "labrador.http.listenAddress" ' .
                    'storing the address the socket should listen on.',
                    self::class
                );
                throw new InvalidStateException($msg);
            }

            $router = new FastRouteRouter(
                new RouteCollector(new RouteParser\Std(), new DataGenerator\GroupCountBased()),
                function($data) { return new Dispatcher\GroupCountBased($data); }
            );
            $injector->share($router);
            $injector->alias(Router::class, get_class($router));
            $injector->share(Application::class);
            $injector->alias(Application::class, DefaultHttpApplication::class);
            $injector->alias(HttpApplication::class, DefaultHttpApplication::class);
            // We are delegating this instead of setting a define so that our Socket doesn't start listening until necessary
            $injector->delegate(DefaultHttpApplication::class, function() use($injector, $settings) {
                $pluginManager = $injector->make(Pluggable::class);
                $emitter = $injector->make(EventEmitter::class);
                $router = $injector->make(Router::class);
                $app = new DefaultHttpApplication(
                    $pluginManager,
                    $emitter,
                    $router,
                    Server::listen('tcp://' . $settings->get('labrador.http.listenAddress'))
                );
                $app->registerPluginLoadHandler(
                    RouterPlugin::class,
                    function(RouterPlugin $routerPlugin) use($router) {
                        $routerPlugin->registerRoutes($router);
                    }
                );
                return $app;
            });
        } catch (InjectorException $injectorException) {
            throw new DependencyInjectionException(
                'There was an error defining the HTTP Application dependencies',
                0,
                $injectorException
            );
        }

        return $injector;
    }

}