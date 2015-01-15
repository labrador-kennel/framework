<?php

/**
 * 
 * @license See LICENSE in source root
 * @version 1.0
 * @since   1.0
 */

namespace Labrador\Http;

use Labrador\CoreEngine;
use Labrador\Http\Event\AfterControllerEvent;
use Labrador\Http\Event\BeforeControllerEvent;
use Labrador\Http\Exception\InvalidTypeException;
use Labrador\Plugin\PluginManager;
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

    public function handleRequest(Request $request) {
        $resolved = $this->router->match($request);
        $this->emitter->emit(self::BEFORE_CONTROLLER_EVENT, [new BeforeControllerEvent($request)]);
        $controller = $resolved->getController();
        $response = $controller($request);
        if (!$response instanceof Response) {
            $msg = 'Controller MUST return an instance of %s, "%s" was returned.';
            throw new InvalidTypeException(sprintf($msg, Response::class, gettype($response)));
        }
        $this->emitter->emit(self::AFTER_CONTROLLER_EVENT, [new AfterControllerEvent($request)]);
        return $response;
    }

    public function get($pattern, $handler) {
        $this->router->addRoute('GET', $pattern, $handler);
    }

    public function post($pattern, $handler) {
        $this->router->addRoute('POST', $pattern, $handler);
    }

    public function put($pattern, $handler) {
        $this->router->addRoute('PUT', $pattern, $handler);
    }

    public function delete($pattern, $handler) {
        $this->router->addRoute('DELETE', $pattern, $handler);
    }

    public function customMethod($method, $pattern, $handler) {
        $this->router->addRoute($method, $pattern, $handler);
    }

} 
