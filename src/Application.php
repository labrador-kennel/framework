<?php

namespace Cspray\Labrador\Http;

use Amp\Http\Server\Middleware;
use Cspray\AnnotatedContainer\Attribute\Service;
use Cspray\Labrador\Http\Middleware\Priority;
use Cspray\Labrador\Http\Router\Router;

#[Service]
interface Application {

    public function getRouter() : Router;

    public function addMiddleware(Middleware $middleware, Priority $priority = Priority::Low) : void;

    public function start() : void;

    public function stop() : void;

}