<?php

/**
 * 
 * @license See LICENSE in source root
 * @version 1.0
 * @since   1.0
 */

namespace Cspray\Labrador\Http\Test\Router;

use Cspray\Labrador\Http\Router\ControllerActionResolver;
use Cspray\Labrador\Http\Exception\InvalidHandlerException;
use Cspray\Labrador\Http\Test\Stub\HandlerWithOutMethod;
use Cspray\Labrador\Http\Test\Stub\HandlerWithMethod;
use Auryn\Injector;
use Symfony\Component\HttpFoundation\Request;
use PHPUnit_Framework_TestCase as UnitTestCase;

class ControllerActionResolverTest extends UnitTestCase {

    function testNoHashTagInHandlerReturnsFalse() {
        $handler = 'something_no_hashtag';
        $Injector = new Injector();
        $resolver = new ControllerActionResolver($Injector);

        $this->assertFalse($resolver->resolve($handler));
    }

    function testNoClassThrowsException() {
        $handler = 'Not_Found_Class#action';
        $Injector = new Injector();
        $resolver = new ControllerActionResolver($Injector);

        $this->setExpectedException(
            InvalidHandlerException::class,
            'An error was encountered creating the controller for Not_Found_Class#action.'
        );
        $resolver->resolve($handler);
    }

    function testNoMethodOnControllerThrowsException() {
        $handler = HandlerWithOutMethod::class . '#action';
        $Injector = new Injector();
        $resolver = new ControllerActionResolver($Injector);

        $this->setExpectedException(
            InvalidHandlerException::class,
            'The controller and action, ' . HandlerWithOutMethod::class . '::action, is not callable. Please ensure that a publicly accessible method is available with this name.'
        );
        $resolver->resolve($handler);
    }

    function testValidControllerActionResultsInRightCallback() {
        $handler = HandlerWithMethod::class . '#action';
        $val = new \stdClass();
        $val->action = null;
        $Injector = new Injector();
        $Injector->define(HandlerWithMethod::class, [':val' => $val]);
        $resolver = new ControllerActionResolver($Injector);

        $cb = $resolver->resolve($handler);
        $cb($this->getMock(Request::class));

        $this->assertSame('invoked', $val->action);
    }

}
