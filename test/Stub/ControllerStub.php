<?php

declare(strict_types=1);

namespace Cspray\Labrador\Http\Test\Stub;

use Cspray\Labrador\Http\Event\AfterControllerEvent;
use Cspray\Labrador\Http\Event\BeforeControllerEvent;
use Zend\Diactoros\Response\TextResponse;

class ControllerStub {

    private $beforeController = 0;
    private $afterController = 0;

    public function index() {
        return new TextResponse('foo');
    }

    public function beforeController(BeforeControllerEvent $event) {
        $this->beforeController++;
    }

    public function beforeControllerCount() {
        return $this->beforeController;
    }

    public function wasBeforeControllerInvoked() {
        return $this->beforeController > 0;
    }

    public function afterController(AfterControllerEvent $event) {
        $this->afterController++;
    }

    public function afterControllerCount() {
        return $this->afterController;
    }

    public function wasAfterControllerInvoked() {
        return $this->afterController > 0;
    }

}