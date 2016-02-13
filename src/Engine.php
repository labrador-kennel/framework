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
use Cspray\Labrador\Http\Event\AfterControllerEvent;
use Cspray\Labrador\Http\Event\BeforeControllerEvent;
use Cspray\Labrador\PluginManager;
use Cspray\Labrador\Event\AppExecuteEvent;
use Cspray\Labrador\Http\Router\Router;
use Cspray\Labrador\Http\Exception\InvalidTypeException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use League\Event\EmitterInterface;

class Engine extends CoreEngine {

    const BEFORE_CONTROLLER_EVENT = 'labrador.http.before_controller';
    const AFTER_CONTROLLER_EVENT = 'labrador.http.after_controller';

    private $emitter;
    private $router;
    private $eventFactory;

    /**
     * @param Router $router
     * @param EmitterInterface $emitter
     * @param PluginManager $pluginManager
     */
    public function __construct(
        Router $router,
        EmitterInterface $emitter,
        PluginManager $pluginManager
    ) {
        parent::__construct($pluginManager, $emitter);
        $this->emitter = $emitter;
        $this->router = $router;
    }

    public function run(Request $req = null) {
        $cb = function(AppExecuteEvent $event) use($req) {
            $this->handleRequest($req)->send();
        };
        $cb = $cb->bindTo($this);
        $this->emitter->addListener(self::APP_EXECUTE_EVENT, $cb);
        parent::run();
    }

    /**
     * @param Request $request
     * @return Response
     * @throws InvalidTypeException
     */
    private function handleRequest(Request $request) : Response {
        $resolved = $this->router->match($request);

        $beforeEvent = new BeforeControllerEvent($request, $resolved->getController());
        $this->emitter->emit($beforeEvent);          // TODO pass $engine and $request
        $response = $beforeEvent->getResponse();

        if (!$response instanceof Response) {
            $controller = $beforeEvent->getController();
            $response = $controller($request);

            if (!$response instanceof Response) {
                $msg = 'Controller MUST return an instance of %s, "%s" was returned.';
                throw new InvalidTypeException(sprintf($msg, Response::class, gettype($response)));
            }

            $afterEvent = new AfterControllerEvent($request, $response, $controller);
            $this->emitter->emit($afterEvent);              // TODO pass $engine and $request and $response
            $response = $afterEvent->getResponse();
        }

        return $response;
    }

    /**
     * @param callable $listener
     * @return $this
     */
    public function onBeforeController(callable $listener) : self {
        $this->emitter->addListener(self::BEFORE_CONTROLLER_EVENT, $listener);
        return $this;
    }

    /**
     * @param callable $listener
     * @return $this
     */
    public function onAfterController(callable $listener) : self {
        $this->emitter->addListener(self::AFTER_CONTROLLER_EVENT, $listener);
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
