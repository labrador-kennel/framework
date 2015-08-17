<?php

namespace Cspray\Labrador\Http\Test;

use Cspray\Labrador\Engine;
use Cspray\Labrador\Event\ExceptionThrownEvent;
use Cspray\Labrador\Http\ExceptionHandlingPlugin;
use League\Event\Emitter as EventEmitter;
use Whoops\Run;
use PHPUnit_Framework_TestCase as UnitTestCase;

class ExceptionHandlingPluginTest extends UnitTestCase {

    public function testHandlesExceptionIfNoExceptionListeners() {
        $exception = new \Exception('Thrown exception');

        $run = $this->getMockBuilder(Run::class)->disableOriginalConstructor()->getMock();
        $run->expects($this->once())->method('handleException')->with($exception);

        $emitter = new EventEmitter();
        (new ExceptionHandlingPlugin($run))->registerEventListeners($emitter);

        $event = new ExceptionThrownEvent($exception);
        $emitter->emit($event);
    }

    public function testDoesNotRunIfHandlersPresent() {
        $exception = new \Exception('Thrown exception');

        $run = $this->getMockBuilder(Run::class)->disableOriginalConstructor()->getMock();
        $run->expects($this->never())->method('handleException');

        $emitter = new EventEmitter();
        (new ExceptionHandlingPlugin($run))->registerEventListeners($emitter);
        $emitter->addListener(Engine::EXCEPTION_THROWN_EVENT, function() {});

        $event = new ExceptionThrownEvent($exception);
        $emitter->emit($event);
    }

}