<?php

/**
 * Hooks into the Engine::APP_EXECUTE_EVENT to invoke a controller
 * that handles a Http\Request and returns a Http\Response.
 *
 * @license See LICENSE in source root
 * @version 1.0
 * @since   1.0
 */

namespace Cspray\Labrador\Http;

use Cspray\Labrador\CoreEngine;
use Cspray\Labrador\PluginManager;
use Cspray\Labrador\Http\Event\AppExecuteEvent;
use Cspray\Labrador\Http\Event\HttpEventFactory;
use Cspray\Labrador\Http\Router\Router;
use Cspray\Labrador\Http\Exception\InvalidTypeException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Evenement\EventEmitterInterface;
use Telluris\Environment;

class Engine extends CoreEngine {

    const BEFORE_CONTROLLER_EVENT = 'labrador.http.before_controller';
    const AFTER_CONTROLLER_EVENT = 'labrador.http.after_controller';

    private $emitter;
    private $router;
    private $eventFactory;

    /**
     * @param Router $router
     * @param EventEmitterInterface $emitter
     * @param PluginManager $pluginManager
     */
    public function __construct(
        Router $router,
        Environment $environment,
        EventEmitterInterface $emitter,
        PluginManager $pluginManager,
        HttpEventFactory $eventFactory
    ) {
        parent::__construct($environment, $pluginManager, $emitter, $eventFactory);
        $this->emitter = $emitter;
        $this->router = $router;
        $this->eventFactory = $eventFactory;
    }

    public function getName() : string {
        return 'Labrador HTTP';
    }

    public function getVersion() : string {
        return '0.1.0-alpha';
    }

    public function run() {
        $cb = function(AppExecuteEvent $event) {
            $this->handleRequest($event->getRequest())->send();
        };
        $cb = $cb->bindTo($this);
        $this->emitter->on(self::APP_EXECUTE_EVENT, $cb);
        parent::run();
    }

    /**
     * @param Request $request
     * @return Response
     * @throws InvalidTypeException
     */
    private function handleRequest(Request $request) : Response {
        $resolved = $this->router->match($request);

        $beforeEvent = $this->eventFactory->create(Engine::BEFORE_CONTROLLER_EVENT, $resolved->getController());
        $this->emitter->emit(self::BEFORE_CONTROLLER_EVENT, [$beforeEvent]);
        $response = $beforeEvent->getResponse();

        if (!$response instanceof Response) {
            $controller = $beforeEvent->getController();
            $response = $controller($request);

            if (!$response instanceof Response) {
                $msg = 'Controller MUST return an instance of %s, "%s" was returned.';
                throw new InvalidTypeException(sprintf($msg, Response::class, gettype($response)));
            }

            $afterEvent = $this->eventFactory->create(Engine::AFTER_CONTROLLER_EVENT, $response, $controller);
            $this->emitter->emit(self::AFTER_CONTROLLER_EVENT, [$afterEvent]);
            $response = $afterEvent->getResponse();
        }

        return $response;
    }

    /**
     * @param callable $listener
     * @return $this
     */
    public function onBeforeController(callable $listener) : self {
        $this->emitter->on(self::BEFORE_CONTROLLER_EVENT, $listener);
        return $this;
    }

    /**
     * @param callable $listener
     * @return $this
     */
    public function onAfterController(callable $listener) : self {
        $this->emitter->on(self::AFTER_CONTROLLER_EVENT, $listener);
        return $this;
    }

    /**
     * @param string $pattern
     * @param mixed $handler
     * @return $this
     */
    public function get($pattern, $handler) : self {
        $this->router->addRoute('GET', $pattern, $handler);
        return $this;
    }

    /**
     * @param string $pattern
     * @param mixed $handler
     * @return $this
     */
    public function post($pattern, $handler) : self {
        $this->router->addRoute('POST', $pattern, $handler);
        return $this;
    }

    /**
     * @param string $pattern
     * @param mixed $handler
     * @return $this
     */
    public function put($pattern, $handler) : self {
        $this->router->addRoute('PUT', $pattern, $handler);
        return $this;
    }

    /**
     * @param string $pattern
     * @param mixed $handler
     * @return $this
     */
    public function patch($pattern, $handler) : self {
        $this->router->addRoute('PATCH', $pattern, $handler);
        return $this;
    }

    /**
     * @param string $pattern
     * @param mixed $handler
     * @return $this
     */
    public function delete($pattern, $handler) : self {
        $this->router->addRoute('DELETE', $pattern, $handler);
        return $this;
    }

    /**
     * @param string $method
     * @param string $pattern
     * @param mixed $handler
     * @return $this
     */
    public function customMethod($method, $pattern, $handler) : self {
        $this->router->addRoute($method, $pattern, $handler);
        return $this;
    }

} 
