<?php

/**
 *
 * @license See LICENSE in source root
 * @version 1.0
 * @since   1.0
 */

namespace Cspray\Labrador\Http\Test;

use Cspray\Labrador\Event\ExceptionThrownEvent;
use Cspray\Labrador\Http\Event\HttpEventFactory;
use Cspray\Labrador\PluginManager;
use Cspray\Labrador\Http\Event\BeforeControllerEvent;
use Cspray\Labrador\Http\Event\AfterControllerEvent;
use Cspray\Labrador\Http\Engine;
use Cspray\Labrador\Http\Router\ResolvedRoute;
use Cspray\Labrador\Http\Router\Router;
use Cspray\Labrador\Http\Exception\InvalidTypeException;
use League\Event\EmitterInterface;
use League\Event\Emitter as EventEmitter;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use PHPUnit_Framework_TestCase as UnitTestCase;

class EngineTest extends UnitTestCase {

    private $mockRouter;
    private $mockEmitter;
    private $mockPluginManager;

    public function setUp() {
        $this->mockRouter = $this->getMock(Router::class);
        $this->mockEmitter = $this->getMock(EmitterInterface::class);
        $this->mockPluginManager = $this->getMockBuilder(PluginManager::class)->disableOriginalConstructor()->getMock();
    }

    private function getMockedEngine(Router $router = null, EmitterInterface $emitter = null) {
        $router = $router ?: $this->mockRouter;
        $emitter = $emitter ?: $this->mockEmitter;
        return new Engine($router, $emitter, $this->mockPluginManager);
    }

    private function runEngine(Engine $engine, Request $request) {
        if (!ob_start()) {
            return $this->fail('Could not start output buffering');
        }
        $engine->run($request);
        return ob_get_clean();
    }

    public function testRequestRouted() {
        $req = Request::create('http://test.example.com');
        $resolved = new ResolvedRoute($req, function() { return new Response(); }, Response::HTTP_OK);
        $this->mockRouter->expects($this->once())
                         ->method('match')
                         ->with($req)
                         ->willReturn($resolved);

        $emitter = new EventEmitter();
        $this->runEngine($this->getMockedEngine(null, $emitter), $req);
    }

    public function eventEmittingOrderProvider() {
        return [
            [0, Engine::BEFORE_CONTROLLER_EVENT, BeforeControllerEvent::class],
            [1, Engine::AFTER_CONTROLLER_EVENT, AfterControllerEvent::class],
        ];
    }

    /**
     * @dataProvider eventEmittingOrderProvider
     */
    public function testEventsTriggered($index, $event, $eventType) {
        $req = Request::create('http://test.example.com');
        $resolved = new ResolvedRoute($req, function() { return new Response(); }, Response::HTTP_OK);

        $this->mockRouter->expects($this->once())
                         ->method('match')
                         ->with($req)
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
        $req = Request::create('http://test.example.com');
        $resolved = new ResolvedRoute($req, function() { return 'not a response'; }, Response::HTTP_OK);

        $this->mockRouter->expects($this->once())
                         ->method('match')
                         ->with($req)
                         ->willReturn($resolved);


        $emitter = new EventEmitter();
        $actual = [];
        $emitter->addListener(Engine::EXCEPTION_THROWN_EVENT, function(ExceptionThrownEvent $event) use(&$actual) {
            $actual = [get_class($event->getException()), $event->getException()->getMessage()];
        });


        $this->runEngine($this->getMockedEngine(null, $emitter), $req);

        list($actualType, $actualMsg) = $actual;
        $expectedExcType = InvalidTypeException::class;
        $expectedMsg = "Controller MUST return an instance of Symfony\\Component\\HttpFoundation\\Response, \"string\" was returned.";

        $this->assertSame($expectedExcType, $actualType);
        $this->assertSame($expectedMsg, $actualMsg);
    }

    public function testDecoratingControllerInBeforeControllerEvent() {
        $req = Request::create('http://test.example.com');
        $resolved = new ResolvedRoute($req, function() { return new Response('From controller'); }, Response::HTTP_OK);

        $this->mockRouter->expects($this->once())
                         ->method('match')
                         ->with($req)
                         ->willReturn($resolved);

        $emitter = new EventEmitter();
        $emitter->addListener(Engine::BEFORE_CONTROLLER_EVENT, function(BeforeControllerEvent $event) {
            $oldController = $event->getController();
            $newController = function(Request $request) use($oldController) {
                $response = $oldController($request);
                return new Response($response->getContent() . ' and the decorator');
            };
            $event->setController($newController);
        });


        $response = $this->runEngine($this->getMockedEngine(null, $emitter), $req);
        $this->assertSame('From controller and the decorator', $response);
    }

    public function testShortCircuitController() {
        $req = Request::create('http://test.example.com');
        $resolved = new ResolvedRoute($req, function() { return new Response('From controller'); }, Response::HTTP_OK);

        $this->mockRouter->expects($this->once())
                         ->method('match')
                         ->with($req)
                         ->willReturn($resolved);

        $emitter = new EventEmitter();
        $emitter->addListener(Engine::BEFORE_CONTROLLER_EVENT, function(BeforeControllerEvent $event) {
            $response = new Response('From the event');
            $event->setResponse($response);
        });

        $response = $this->runEngine($this->getMockedEngine(null, $emitter), $req);

        $this->assertSame('From the event', $response);
    }

    public function testDecoratingResponseInAfterController() {
        $req = Request::create('http://test.example.com');
        $resolved = new ResolvedRoute($req, function() { return new Response('From controller'); }, Response::HTTP_OK);

        $this->mockRouter->expects($this->once())
                         ->method('match')
                         ->with($req)
                         ->willReturn($resolved);

        $emitter = new EventEmitter();
        $emitter->addListener(Engine::AFTER_CONTROLLER_EVENT, function(AfterControllerEvent $event) {
            $response = $event->getResponse();
            $event->setResponse(new Response($response->getContent() . ' and the decorator'));
        });

        $response = $this->runEngine($this->getMockedEngine(null, $emitter), $req);

        $this->assertSame('From controller and the decorator', $response);
    }

    public function testGettingControllerFromAfterControllerEvent() {
        $req = Request::create('http://test.example.com');
        $controller = function() { return new Response('something'); };
        $resolved = new ResolvedRoute($req, $controller, Response::HTTP_OK);

        $this->mockRouter->expects($this->once())
                         ->method('match')
                         ->with($req)
                         ->willReturn($resolved);

        $emitter = new EventEmitter();
        $check = false;
        $emitter->addListener(Engine::AFTER_CONTROLLER_EVENT, function(AfterControllerEvent $event) use($controller, &$check) {
            $check = $controller === $event->getController();
        });

        $this->runEngine($this->getMockedEngine(null, $emitter), $req);
        $this->assertTrue($check);
    }

}
