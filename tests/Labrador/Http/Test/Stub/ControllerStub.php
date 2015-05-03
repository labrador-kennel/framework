<?php


namespace Labrador\Http\Test\Stub;


use Labrador\Http\Controller\Controller;
use Labrador\Http\Event\AfterControllerEvent;
use Labrador\Http\Event\BeforeControllerEvent;
use Symfony\Component\HttpFoundation\Response;

class ControllerStub extends Controller {

    private $beforeController = false;
    private $afterController = false;

    public function index() {
        return new Response('foo');
    }

    public function beforeController(BeforeControllerEvent $event) {
        $this->beforeController = true;
    }

    public function wasBeforeControllerInvoked() {
        return $this->beforeController;
    }

    public function afterController(AfterControllerEvent $event) {
        $this->afterController = true;
    }

    public function wasAfterControllerInvoked() {
        return $this->afterController;
    }

}