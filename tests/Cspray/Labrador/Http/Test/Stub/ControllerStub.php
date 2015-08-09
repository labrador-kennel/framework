<?php

declare(strict_types=1);

namespace Cspray\Labrador\Http\Test\Stub;

use Cspray\Labrador\Http\Controller\Controller;
use Cspray\Labrador\Http\Event\AfterControllerEvent;
use Cspray\Labrador\Http\Event\BeforeControllerEvent;
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