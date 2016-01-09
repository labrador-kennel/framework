<?php

/**
 *
 * @license See LICENSE in source root
 * @version 1.0
 * @since   1.0
 */

namespace Cspray\Labrador\Http\Test;

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

    private function getMockedEngine(Request $request, Router $router = null, EmitterInterface $emitter = null) {
        $router = $router ?: $this->mockRouter;
        $emitter = $emitter ?: $this->mockEmitter;
        $factory = new HttpEventFactory($request);
        return new Engine($router, $emitter, $this->mockPluginManager, $factory);
    }

    private function getHandleRequestExecutable(Engine $engine, Request $request) {
        return function() use($engine, $request) {
            $r = new \ReflectionClass($engine);
            $m = $r->getMethod('handleRequest');
            $m->setAccessible(true);
            return $m->invokeArgs($engine, [$request]);
        };
    }

    public function testRequestRouted() {
        $req = Request::create('http://test.example.com');
        $resolved = new ResolvedRoute($req, function() { return new Response(); }, Response::HTTP_OK);
        $this->mockRouter->expects($this->once())
                         ->method('match')
                         ->with($req)
                         ->willReturn($resolved);

        $engine = $this->getMockedEngine($req);
        $this->getHandleRequestExecutable($engine, $req)();
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

        $this->mockEmitter->expects($this->at($index))
                          ->method('emit')
                          ->with(
                              $this->callback(function($arg) use($eventType) {
                                  return $arg instanceof $eventType;
                              })
                          );

        $engine = $this->getMockedEngine($req);
        $this->getHandleRequestExecutable($engine, $req)();
    }

    public function testControllerMustReturnResponse() {
        $req = Request::create('http://test.example.com');
        $resolved = new ResolvedRoute($req, function() { return 'not a response'; }, Response::HTTP_OK);

        $this->mockRouter->expects($this->once())
                         ->method('match')
                         ->with($req)
                         ->willReturn($resolved);

        $msg = 'Controller MUST return an instance of Symfony\\Component\\HttpFoundation\\Response, "string" was returned.';
        $this->setExpectedException(InvalidTypeException::class, $msg);

        $engine = $this->getMockedEngine($req);
        $this->getHandleRequestExecutable($engine, $req)();
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

        $engine = $this->getMockedEngine($req, null, $emitter);
        $response = $this->getHandleRequestExecutable($engine, $req)();

        $this->assertSame('From controller and the decorator', $response->getContent());
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

        $engine = $this->getMockedEngine($req, null, $emitter);
        $response = $this->getHandleRequestExecutable($engine, $req)();

        $this->assertSame('From the event', $response->getContent());
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

        $engine = $this->getMockedEngine($req, null, $emitter);
        $response = $this->getHandleRequestExecutable($engine, $req)();
        $this->assertSame('From controller and the decorator', $response->getContent());
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

        $engine = $this->getMockedEngine($req, null, $emitter);
        $this->getHandleRequestExecutable($engine, $req)();
        $this->assertTrue($check);
    }

}
