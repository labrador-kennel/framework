<?php

namespace Cspray\Labrador\Http\Test\Controller;

use Cspray\Labrador\Http\Controller\EventTriggeringPlugin;
use Cspray\Labrador\Http\Engine;
use Cspray\Labrador\Http\Event\AfterControllerEvent;
use Cspray\Labrador\Http\Event\BeforeControllerEvent;
use Cspray\Labrador\Http\Test\Stub\ControllerStub;
use Cspray\Labrador\Http\Test\Stub\HandlerNotControllerStub;
use Evenement\EventEmitter;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use PHPUnit_Framework_TestCase as UnitTestCase;


class EventTriggeringPluginTest extends UnitTestCase {

    public function controllerEventTriggeredData() {
        $beforeController = function(callable $controller) {
            $req = $this->getMock(Request::class);
            return new BeforeControllerEvent($req, $controller);
        };

        $afterController = function(callable $controller) {
            $req = $this->getMock(Request::class);
            $res = $this->getMock(Response::class);
            return new AfterControllerEvent($req, $res, $controller);
        };
        return [
            [$beforeController, Engine::BEFORE_CONTROLLER_EVENT, 'wasBeforeControllerInvoked'],
            [$afterController, Engine::AFTER_CONTROLLER_EVENT, 'wasAfterControllerInvoked']
        ];
    }

    /**
     * @param callable $eventFactory
     * @param $eventName
     * @param $eventCheck
     *
     * @dataProvider controllerEventTriggeredData
     */
    public function testControllerEventTriggered(callable $eventFactory, $eventName, $eventCheck) {
        $emitter = new EventEmitter();
        $plugin = new EventTriggeringPlugin();
        $plugin->registerEventListeners($emitter);

        $controller = [$obj = new ControllerStub(), 'index'];
        $event = $eventFactory($controller);

        $emitter->emit($eventName, [$event]);

        $this->assertTrue($obj->$eventCheck());
    }

    public function controllerNoErrorData() {
        $anonFuncBefore = function() {
            $req = $this->getMock(Request::class);
            $controller = function() {};
            return new BeforeControllerEvent($req, $controller);
        };

        $notControllerInstanceBefore = function() {
            $req = $this->getMock(Request::class);
            $controller = [new HandlerNotControllerStub(), 'index'];
            return new BeforeControllerEvent($req, $controller);
        };

        $anonFuncAfter = function() {
            $req = $this->getMock(Request::class);
            $res = $this->getMock(Response::class);
            $controller = function() {};
            return new AfterControllerEvent($req, $res, $controller);
        };

        $notControllerInstanceAfter = function() {
            $req = $this->getMock(Request::class);
            $res = $this->getMock(Response::class);
            $controller = [new HandlerNotControllerStub(), 'index'];
            return new AfterControllerEvent($req, $res, $controller);
        };

        return [
            [$anonFuncBefore, Engine::BEFORE_CONTROLLER_EVENT],
            [$notControllerInstanceBefore, Engine::BEFORE_CONTROLLER_EVENT],
            [$anonFuncAfter, Engine::AFTER_CONTROLLER_EVENT],
            [$notControllerInstanceAfter, Engine::AFTER_CONTROLLER_EVENT]
        ];
    }

    /**
     * @param callable $eventFactory
     * @param $eventName
     *
     * @dataProvider controllerNoErrorData
     */
    public function testEventTriggerHandlesControllersWithoutCallback(callable $eventFactory, $eventName) {
        $emitter = new EventEmitter();
        $plugin = new EventTriggeringPlugin();
        $plugin->registerEventListeners($emitter);

        $event = $eventFactory();

        $errorThrown = false;
        set_error_handler(function() use(&$errorThrown) {
            $errorThrown = true;
        });

        $exc = null;
        try {
            $emitter->emit($eventName, [$event]);
        } catch (\Exception $exc) {}

        restore_error_handler();

        $this->assertFalse($errorThrown);
        $this->assertNull($exc);
    }

}