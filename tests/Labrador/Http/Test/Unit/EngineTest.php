<?php

/**
 * 
 * @license See LICENSE in source root
 * @version 1.0
 * @since   1.0
 */

namespace Labrador\Test\Unit;

use Labrador\Plugin\PluginManager;
use Labrador\Http\Event\BeforeControllerEvent;
use Labrador\Http\Event\AfterControllerEvent;
use Labrador\Http\Engine;
use Labrador\Http\ResolvedRoute;
use Labrador\Http\Router;
use Labrador\Http\Exception\InvalidTypeException;
use Evenement\EventEmitterInterface;
use PHPUnit_Framework_TestCase as UnitTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class EngineTest extends UnitTestCase {

    private $mockRouter;
    private $mockEmitter;
    private $mockPluginManager;

    public function setUp() {
        $this->mockRouter = $this->getMock(Router::class);
        $this->mockEmitter = $this->getMock(EventEmitterInterface::class);
        $this->mockPluginManager = $this->getMockBuilder(PluginManager::class)->disableOriginalConstructor()->getMock();
    }

    private function getMockedEngine() {
        return new Engine($this->mockRouter, $this->mockEmitter, $this->mockPluginManager);
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

} 
