<?php

/**
 * 
 * @license See LICENSE in source root
 * @version 1.0
 * @since   1.0
 */

namespace Cspray\Labrador\Http\Test;

use Cspray\Labrador\PluginManager;
use Cspray\Labrador\Http\Event\BeforeControllerEvent;
use Cspray\Labrador\Http\Event\AfterControllerEvent;
use Cspray\Labrador\Http\Engine;
use Cspray\Labrador\Http\Router\ResolvedRoute;
use Cspray\Labrador\Http\Router\Router;
use Cspray\Labrador\Http\Exception\InvalidTypeException;
use Evenement\EventEmitterInterface;
use Evenement\EventEmitter;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Telluris\Environment;
use PHPUnit_Framework_TestCase as UnitTestCase;

class EngineTest extends UnitTestCase {

    private $mockRouter;
    private $mockEmitter;
    private $mockPluginManager;
    private $mockEnvironment;

    public function setUp() {
        $this->mockRouter = $this->getMock(Router::class);
        $this->mockEmitter = $this->getMock(EventEmitterInterface::class);
        $this->mockPluginManager = $this->getMockBuilder(PluginManager::class)->disableOriginalConstructor()->getMock();
        $this->mockEnvironment = $this->getMockBuilder(Environment::class)->disableOriginalConstructor()->getMock();
    }

    private function getMockedEngine(Router $router = null, EventEmitterInterface $emitter = null) {
        $router = $router ?: $this->mockRouter;
        $emitter = $emitter ?: $this->mockEmitter;
        return new Engine($router, $this->mockEnvironment, $emitter, $this->mockPluginManager);
    }

    public function testRequestRouted() {
        $req = Request::create('http://test.example.com');
        $resolved = new ResolvedRoute($req, function() { return new Response(); }, Response::HTTP_OK);
        $this->mockRouter->expects($this->once())
                         ->method('match')
                         ->with($req)
                         ->willReturn($resolved);

        $this->getMockedEngine()->handleRequest($req);
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
                              $event,
                              $this->callback(function($args) use($eventType) {
                                  return $args[0] instanceof $eventType;
                              })
                          );

        $this->getMockedEngine()->handleRequest($req);
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

        $this->getMockedEngine()->handleRequest($req);
    }

    public function testDecoratingControllerInBeforeControllerEvent() {
        $req = Request::create('http://test.example.com');
        $resolved = new ResolvedRoute($req, function() { return new Response('From controller'); }, Response::HTTP_OK);

        $this->mockRouter->expects($this->once())
                         ->method('match')
                         ->with($req)
                         ->willReturn($resolved);

        $emitter = new EventEmitter();
        $emitter->on(Engine::BEFORE_CONTROLLER_EVENT, function(BeforeControllerEvent $event) {
            $oldController = $event->getController();
            $newController = function(Request $request) use($oldController) {
                $response = $oldController($request);
                return new Response($response->getContent() . ' and the decorator');
            };
            $event->setController($newController);
        });

        $response = $this->getMockedEngine(null, $emitter)->handleRequest($req);

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
        $emitter->on(Engine::BEFORE_CONTROLLER_EVENT, function(BeforeControllerEvent $event) {
            $response = new Response('From the event');
            $event->setResponse($response);
        });

        $response = $this->getMockedEngine(null, $emitter)->handleRequest($req);

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
        $emitter->on(Engine::AFTER_CONTROLLER_EVENT, function(AfterControllerEvent $event) {
            $response = $event->getResponse();
            $event->setResponse(new Response($response->getContent() . ' and the decorator'));
        });

        $response = $this->getMockedEngine(null, $emitter)->handleRequest($req);
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
        $emitter->on(Engine::AFTER_CONTROLLER_EVENT, function(AfterControllerEvent $event) use($controller, &$check) {
            $check = $controller === $event->getController();
        });

        $this->getMockedEngine(null, $emitter)->handleRequest($req);
        $this->assertTrue($check);
    }

} 
