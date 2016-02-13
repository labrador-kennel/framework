<?php

/**
 *
 * @license See LICENSE in source root
 * @version 1.0
 * @since   1.0
 */

namespace Cspray\Labrador\Http\Test\HandlerResolver;

use Cspray\Labrador\Http\Event\AfterControllerEvent;
use Cspray\Labrador\Http\Event\BeforeControllerEvent;
use Cspray\Labrador\Http\HandlerResolver\ControllerActionResolver;
use Cspray\Labrador\Http\Exception\InvalidHandlerException;
use Cspray\Labrador\Http\Test\Stub\HandlerWithOutMethod;
use Cspray\Labrador\Http\Test\Stub\HandlerWithMethod;
use Cspray\Labrador\Http\Test\Stub\ControllerStub;
use Auryn\Injector;
use Symfony\Component\HttpFoundation\Request;
use League\Event\Emitter;
use PHPUnit_Framework_TestCase as UnitTestCase;

class ControllerActionResolverTest extends UnitTestCase {

    private $request;
    private $emitter;

    public function setUp() {
        $this->request = Request::create('/');
        $this->emitter = new Emitter();
    }

    function testNoHashTagInHandlerReturnsFalse() {
        $handler = 'something_no_hashtag';
        $injector = new Injector();
        $resolver = new ControllerActionResolver($injector, $this->emitter);

        $this->assertFalse($resolver->resolve($this->request, $handler));
    }

    function testNoClassThrowsException() {
        $handler = 'Not_Found_Class#action';
        $injector = new Injector();
        $resolver = new ControllerActionResolver($injector, $this->emitter);

        $this->setExpectedException(
            InvalidHandlerException::class,
            'An error was encountered creating the controller for Not_Found_Class#action.'
        );
        $resolver->resolve($this->request, $handler);
    }

    function testNoMethodOnControllerThrowsException() {
        $handler = HandlerWithOutMethod::class . '#action';
        $injector = new Injector();
        $resolver = new ControllerActionResolver($injector, $this->emitter);

        $this->setExpectedException(
            InvalidHandlerException::class,
            'The controller and action, ' . HandlerWithOutMethod::class . '::action, is not callable. Please ensure that a publicly accessible method is available with this name.'
        );
        $resolver->resolve($this->request, $handler);
    }

    function testValidControllerActionResultsInRightCallback() {
        $handler = HandlerWithMethod::class . '#action';
        $val = new \stdClass();
        $val->action = null;
        $injector = new Injector();
        $injector->define(HandlerWithMethod::class, [':val' => $val]);
        $resolver = new ControllerActionResolver($injector, $this->emitter);

        $cb = $resolver->resolve($this->request, $handler);
        $cb($this->getMock(Request::class));

        $this->assertSame('invoked', $val->action);
    }

    public function testCallsBeforeAndAfterControllerMethodIfPresent() {
        $handler = ControllerStub::class . '#index';

        $injector = new Injector();
        $injector->share(ControllerStub::class);

        $resolver = new ControllerActionResolver($injector, $this->emitter);

        $cb = $resolver->resolve($this->request, $handler);

        $this->emitter->emit(new BeforeControllerEvent($this->request, $cb));
        $res = $cb();
        $this->emitter->emit(new AfterControllerEvent($this->request, $res, $cb));

        $controller = $injector->make(ControllerStub::class);

        $this->assertTrue($controller->wasBeforeControllerInvoked(), 'beforeController not invoked');
        $this->assertTrue($controller->wasAfterControllerInvoked(), 'afterController not invoked');
    }

    public function testCallsBeforeAndAfterControllerOneTime() {
        $handler = ControllerStub::class . '#index';

        $injector = new Injector();
        $injector->share(ControllerStub::class);

        $resolver = new ControllerActionResolver($injector, $this->emitter);

        $cb = $resolver->resolve($this->request, $handler);

        $this->emitter->emit(new BeforeControllerEvent($this->request, $cb));
        $res = $cb();
        $this->emitter->emit(new AfterControllerEvent($this->request, $res, $cb));

        // imagine the engine gets ran again and these events are triggered again
        $this->emitter->emit(new BeforeControllerEvent($this->request, function() {}));
        $this->emitter->emit(new AfterControllerEvent($this->request, $res, function() {}));

        $controller = $injector->make(ControllerStub::class);

        $this->assertSame(1, $controller->beforeControllerCount(), 'beforeController not called exactly 1 time');
        $this->assertSame(1, $controller->afterControllerCount(), 'afterController not called exactly 1 time');
    }

}
