<?php

/**
 * 
 * @license See LICENSE in source root
 * @version 1.0
 * @since   1.0
 */

namespace Labrador\Http\Test\Unit;

use Labrador\Http\Resolver\ControllerActionResolver;
use Labrador\Http\Exception\InvalidHandlerException;
use Labrador\Http\Test\Stub\HandlerWithOutMethod;
use Labrador\Http\Test\Stub\HandlerWithMethod;
use Auryn\Provider;
use Symfony\Component\HttpFoundation\Request;
use PHPUnit_Framework_TestCase as UnitTestCase;

class ControllerActionResolverTest extends UnitTestCase {

    function testNoHashTagInHandlerReturnsFalse() {
        $handler = 'something_no_hashtag';
        $provider = new Provider();
        $resolver = new ControllerActionResolver($provider);

        $this->assertFalse($resolver->resolve($handler));
    }

    function testNoClassThrowsException() {
        $handler = 'Not_Found_Class#action';
        $provider = new Provider();
        $resolver = new ControllerActionResolver($provider);

        $this->setExpectedException(
            InvalidHandlerException::class,
            'An error was encountered creating the controller for Not_Found_Class#action.'
        );
        $resolver->resolve($handler);
    }

    function testNoMethodOnControllerThrowsException() {
        $handler = HandlerWithOutMethod::class . '#action';
        $provider = new Provider();
        $resolver = new ControllerActionResolver($provider);

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
        $provider = new Provider();
        $provider->define(HandlerWithMethod::class, [':val' => $val]);
        $resolver = new ControllerActionResolver($provider);

        $cb = $resolver->resolve($handler);
        $cb($this->getMock(Request::class));

        $this->assertSame('invoked', $val->action);
    }

}
