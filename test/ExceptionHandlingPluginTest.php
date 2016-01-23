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

        $arg = null;
        $run = (new Run())->pushHandler(function($_arg) use(&$arg) {
            $arg = $_arg;
        });

        $emitter = new EventEmitter();
        (new ExceptionHandlingPlugin($run))->registerEventListeners($emitter);

        $event = new ExceptionThrownEvent($exception);
        $emitter->emit($event);

        $this->assertEquals($exception, $arg);
    }

    public function testDoesNotRunIfHandlersPresent() {
        $exception = new \Exception('Thrown exception');

        $notCalled = true;
        $run = (new Run())->pushHandler(function() use(&$notCalled) {
            $notCalled = false;
        });

        $emitter = new EventEmitter();
        (new ExceptionHandlingPlugin($run))->registerEventListeners($emitter);
        $emitter->addListener(Engine::EXCEPTION_THROWN_EVENT, function() {});

        $event = new ExceptionThrownEvent($exception);
        $emitter->emit($event);

        $this->assertTrue($notCalled);
    }

}