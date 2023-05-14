<?php

namespace Labrador\DummyApp;

use Amp\Http\Server\Middleware;
use Cspray\AnnotatedContainer\Attribute\Service;

#[Service]
class MiddlewareCallRegistry {

    private array $called = [];

    public function called(Middleware $middleware) : void {
        $this->called[] = $middleware::class;
    }

    public function getCalled() : array {
        return $this->called;
    }

    public function reset() : void {
        $this->called = [];
    }

}