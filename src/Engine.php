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
use Cspray\Labrador\Http\Event\ResponseSentEvent;
use Cspray\Labrador\PluginManager;
use Cspray\Labrador\Http\Router\Router;
use Cspray\Labrador\Http\Exception\InvalidTypeException;
use League\Event\EmitterInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;
use Cspray\Labrador\Http\ResponseDeliverer\DiactorosAdapter;
use Zend\Diactoros\ServerRequestFactory;

class Engine extends CoreEngine {

    const BEFORE_CONTROLLER_EVENT = 'labrador.http.before_controller';
    const AFTER_CONTROLLER_EVENT = 'labrador.http.after_controller';
    const RESPONSE_SENT_EVENT = 'labrador.http.response_sent';

    private $router;
    private $responseDeliverer;

    /**
     * @param Router $router
     * @param EmitterInterface $emitter
     * @param PluginManager $pluginManager
     */
    public function __construct(
        Router $router,
        EmitterInterface $emitter,
        PluginManager $pluginManager,
        ResponseDeliverer $responseDeliverer = null
    ) {
        parent::__construct($pluginManager, $emitter);
        $this->router = $router;
        $this->responseDeliverer = $responseDeliverer ?? new DiactorosAdapter();
    }

    public function run(ServerRequestInterface $req = null) {
        $cb = function() use($req) {
            $request = $req ?? ServerRequestFactory::fromGlobals();
            $response = $this->handleRequest($request);

            $this->responseDeliverer->deliver($response);

            $event = new ResponseSentEvent($request, $response);
            $this->getEmitter()->emit($event);
        };
        $cb = $cb->bindTo($this);
        $this->getEmitter()->addListener(self::APP_EXECUTE_EVENT, $cb);
        parent::run();
    }

    /**
     * @param ServerRequestInterface $request
     * @return ResponseInterface
     * @throws InvalidTypeException
     */
    private function handleRequest(ServerRequestInterface $request) : ResponseInterface {
        $resolved = $this->router->match($request);
        $request = $resolved->getRequest();

        $beforeEvent = new BeforeControllerEvent($request, $resolved->getController());
        $this->getEmitter()->emit($beforeEvent);          // TODO pass $engine and $request
        $response = $beforeEvent->getResponse();

        if (!$response instanceof ResponseInterface) {
            $controller = $beforeEvent->getController();
            $response = $controller($request);

            if (!$response instanceof ResponseInterface) {
                $msg = 'Controller MUST return an instance of %s, "%s" was returned.';
                throw new InvalidTypeException(sprintf($msg, ResponseInterface::class, gettype($response)));
            }

            $afterEvent = new AfterControllerEvent($request, $response, $controller);
            $this->getEmitter()->emit($afterEvent);              // TODO pass $engine and $request and $response
            $response = $afterEvent->getResponse();
        }

        return $response;
    }

    /**
     * @param callable $listener
     * @return $this
     */
    public function onBeforeController(callable $listener) : self {
        $this->getEmitter()->addListener(self::BEFORE_CONTROLLER_EVENT, $listener);
        return $this;
    }

    /**
     * @param callable $listener
     * @return $this
     */
    public function onAfterController(callable $listener) : self {
        $this->getEmitter()->addListener(self::AFTER_CONTROLLER_EVENT, $listener);
        return $this;
    }

    public function onResponseSent(callable $listener) : self {
        $this->getEmitter()->addListener(self::RESPONSE_SENT_EVENT, $listener);
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
