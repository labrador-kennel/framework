<?php

namespace Labrador\Http\Test;

use Evenement\EventEmitter;
use Labrador\Engine;
use Labrador\Event\ExceptionThrownEvent;
use Labrador\Http\ExceptionHandlingPlugin;
use PHPUnit_Framework_TestCase as UnitTestCase;
use Whoops\Run;

class ExceptionHandlingPluginTest extends UnitTestCase {

    public function testHandlesExceptionIfNoExceptionListeners() {
        $exception = new \Exception('Thrown exception');

        $run = $this->getMockBuilder(Run::class)->disableOriginalConstructor()->getMock();
        $run->expects($this->once())->method('handleException')->with($exception);

        $emitter = new EventEmitter();
        (new ExceptionHandlingPlugin($run))->registerEventListeners($emitter);

        $event = new ExceptionThrownEvent($exception);
        $emitter->emit(Engine::EXCEPTION_THROWN_EVENT, [$event]);
    }

    public function testDoesNotRunIfHandlersPresent() {
        $exception = new \Exception('Thrown exception');

        $run = $this->getMockBuilder(Run::class)->disableOriginalConstructor()->getMock();
        $run->expects($this->never())->method('handleException');

        $emitter = new EventEmitter();
        (new ExceptionHandlingPlugin($run))->registerEventListeners($emitter);
        $emitter->on(Engine::EXCEPTION_THROWN_EVENT, function() {});

        $event = new ExceptionThrownEvent($exception);
        $emitter->emit(Engine::EXCEPTION_THROWN_EVENT, [$event]);
    }

}