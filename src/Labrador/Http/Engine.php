<?php

/**
 * 
 * @license See LICENSE in source root
 * @version 1.0
 * @since   1.0
 */

namespace Labrador\Http;

use Labrador\CoreEngine;
use Labrador\Plugin\PluginManager;
use Labrador\Http\Router\Router;
use Labrador\Http\Event\AfterControllerEvent;
use Labrador\Http\Event\BeforeControllerEvent;
use Labrador\Http\Exception\InvalidTypeException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Evenement\EventEmitterInterface;

class Engine extends CoreEngine {

    const BEFORE_CONTROLLER_EVENT = 'labrador.http.before_controller';
    const AFTER_CONTROLLER_EVENT = 'labrador.http.after_controller';

    private $emitter;
    private $router;

    public function __construct(Router $router, EventEmitterInterface $emitter, PluginManager $pluginManager) {
        parent::__construct($emitter, $pluginManager);
        $this->emitter = $emitter;
        $this->router = $router;
    }

    public function getName() {
        return 'Labrador HTTP';
    }

    public function getVersion() {
        return '0.1.0-alpha';
    }

    public function run() {
        $self = $this;
        $this->emitter->on(self::APP_EXECUTE_EVENT, function() use($self) {
            $request = Request::createFromGlobals();
            $self->handleRequest($request)->send();
        });
        parent::run();
    }

    /**
     * @param Request $request
     * @return Response
     * @throws InvalidTypeException
     */
    public function handleRequest(Request $request) {
        $resolved = $this->router->match($request);

        $beforeEvent = new BeforeControllerEvent($request, $resolved->getController());
        $this->emitter->emit(self::BEFORE_CONTROLLER_EVENT, [$beforeEvent]);
        $response = $beforeEvent->getResponse();

        if (!$response instanceof Response) {
            $controller = $beforeEvent->getController();
            $response = $controller($request);

            if (!$response instanceof Response) {
                $msg = 'Controller MUST return an instance of %s, "%s" was returned.';
                throw new InvalidTypeException(sprintf($msg, Response::class, gettype($response)));
            }

            $afterEvent = new AfterControllerEvent($request);
            $afterEvent->setResponse($response);
            $this->emitter->emit(self::AFTER_CONTROLLER_EVENT, [$afterEvent]);
            $response = $afterEvent->getResponse();
        }

        return $response;
    }

    /**
     * @param callable $listener
     * @return $this
     */
    public function onBeforeController(callable $listener) {
        $this->emitter->on(self::BEFORE_CONTROLLER_EVENT, $listener);
        return $this;
    }

    /**
     * @param callable $listener
     * @return $this
     */
    public function onAfterController(callable $listener) {
        $this->emitter->on(self::AFTER_CONTROLLER_EVENT, $listener);
        return $this;
    }

    /**
     * @param string $pattern
     * @param mixed $handler
     * @return $this
     */
    public function get($pattern, $handler) {
        $this->router->addRoute('GET', $pattern, $handler);
        return $this;
    }

    /**
     * @param string $pattern
     * @param mixed $handler
     * @return $this
     */
    public function post($pattern, $handler) {
        $this->router->addRoute('POST', $pattern, $handler);
        return $this;
    }

    /**
     * @param string $pattern
     * @param mixed $handler
     * @return $this
     */
    public function put($pattern, $handler) {
        $this->router->addRoute('PUT', $pattern, $handler);
        return $this;
    }

    /**
     * @param string $pattern
     * @param mixed $handler
     * @return $this
     */
    public function patch($pattern, $handler) {
        $this->router->addRoute('PATCH', $pattern, $handler);
        return $this;
    }

    /**
     * @param string $pattern
     * @param mixed $handler
     * @return $this
     */
    public function delete($pattern, $handler) {
        $this->router->addRoute('DELETE', $pattern, $handler);
        return $this;
    }

    /**
     * @param string $method
     * @param string $pattern
     * @param mixed $handler
     * @return $this
     */
    public function customMethod($method, $pattern, $handler) {
        $this->router->addRoute($method, $pattern, $handler);
        return $this;
    }

} 
