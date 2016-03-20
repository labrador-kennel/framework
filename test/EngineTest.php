<?php

/**
 *
 * @license See LICENSE in source root
 * @version 1.0
 * @since   1.0
 */

namespace Cspray\Labrador\Http\Test;

use Cspray\Labrador\Event\ExceptionThrownEvent;
use Cspray\Labrador\Http\Event\ResponseSentEvent;
use Cspray\Labrador\Http\ResponseDeliverer;
use Cspray\Labrador\Http\StatusCodes;
use Cspray\Labrador\PluginManager;
use Cspray\Labrador\Http\Event\BeforeControllerEvent;
use Cspray\Labrador\Http\Event\AfterControllerEvent;
use Cspray\Labrador\Http\Engine;
use Cspray\Labrador\Http\Router\ResolvedRoute;
use Cspray\Labrador\Http\Router\Router;
use Cspray\Labrador\Http\Exception\InvalidTypeException;
use League\Event\EmitterInterface;
use League\Event\Emitter as EventEmitter;
use Psr\Http\Message\ServerRequestInterface;
use Zend\Diactoros\ServerRequest as Request;
use Zend\Diactoros\Response;
use PHPUnit_Framework_TestCase as UnitTestCase;
use Zend\Diactoros\Uri;

class EngineTest extends UnitTestCase {

    private $mockRouter;
    private $mockEmitter;
    private $mockDeliverer;
    private $mockPluginManager;

    public function setUp() {
        $this->mockRouter = $this->getMock(Router::class);
        $this->mockEmitter = $this->getMock(EmitterInterface::class);
        $this->mockDeliverer = $this->getMock(ResponseDeliverer::class);
        $this->mockPluginManager = $this->getMockBuilder(PluginManager::class)->disableOriginalConstructor()->getMock();
    }

    private function getMockedEngine(Router $router = null, EmitterInterface $emitter = null, ResponseDeliverer $responseDeliverer = null) {
        $router = $router ?: $this->mockRouter;
        $emitter = $emitter ?: $this->mockEmitter;
        $deliverer = $responseDeliverer ?? $this->mockDeliverer;
        return new Engine($router, $emitter, $this->mockPluginManager, $deliverer);
    }

    private function runEngine(Engine $engine, Request $request, bool $failOnException = true) {
        if (!ob_start()) {
            return $this->fail('Could not start output buffering');
        }

        if ($failOnException) {
            $engine->onExceptionThrown(function(ExceptionThrownEvent $event) {
                $this->fail($event->getException()->getMessage());
            });
        }

        $engine->run($request);
        return ob_get_clean();
    }

    public function testRequestRouted() {
        $req = (new Request())->withMethod('GET')
                              ->withUri(new Uri('http://test.example.com'));

        $resolved = new ResolvedRoute($req, function() { return new Response(); }, StatusCodes::OK);
        $this->mockRouter->expects($this->once())
                         ->method('match')
                         ->with($this->isInstanceOf(ServerRequestInterface::class))
                         ->willReturn($resolved);

        $emitter = new EventEmitter();
        $this->runEngine($this->getMockedEngine(null, $emitter), $req);
    }

    public function eventEmittingOrderProvider() {
        return [
            [0, Engine::BEFORE_CONTROLLER_EVENT, BeforeControllerEvent::class],
            [1, Engine::AFTER_CONTROLLER_EVENT, AfterControllerEvent::class],
            [2, Engine::RESPONSE_SENT_EVENT, ResponseSentEvent::class]
        ];
    }

    /**
     * @dataProvider eventEmittingOrderProvider
     */
    public function testEventsTriggered($index, $event, $eventType) {
        $req = (new Request())->withMethod('GET')
                              ->withUri(new Uri('http://test.example.com'));
        $resolved = new ResolvedRoute($req, function() { return new Response(); }, StatusCodes::OK);

        $this->mockRouter->expects($this->once())
                         ->method('match')
                         ->with($this->isInstanceOf(ServerRequestInterface::class))
                         ->willReturn($resolved);

        $emitter = new EventEmitter();
        $instanceOf = false;
        $emitter->addListener($event, function($arg) use($eventType, &$instanceOf) {
            $instanceOf = $arg instanceof $eventType;
        });

        $this->runEngine($this->getMockedEngine(null, $emitter), $req);

        $this->assertTrue($instanceOf);
    }

    public function testControllerMustReturnResponse() {
        $req = (new Request())->withMethod('GET')
                              ->withUri(new Uri('http://test.example.com'));
        $resolved = new ResolvedRoute($req, function() { return 'not a response'; }, StatusCodes::OK);

        $this->mockRouter->expects($this->once())
                         ->method('match')
                         ->with($this->isInstanceOf(ServerRequestInterface::class))
                         ->willReturn($resolved);


        $emitter = new EventEmitter();
        $actual = [null, null];
        $emitter->addListener(Engine::EXCEPTION_THROWN_EVENT, function(ExceptionThrownEvent $event) use(&$actual) {
            $actual = [get_class($event->getException()), $event->getException()->getMessage()];
        });

        $this->runEngine($this->getMockedEngine(null, $emitter), $req, false);

        list($actualType, $actualMsg) = $actual;
        $expectedExcType = InvalidTypeException::class;
        $expectedMsg = "Controller MUST return an instance of Psr\\Http\\Message\\ResponseInterface, \"string\" was returned.";

        $this->assertSame($expectedExcType, $actualType);
        $this->assertSame($expectedMsg, $actualMsg);
    }

}
