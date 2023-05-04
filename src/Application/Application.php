<?php

namespace Labrador\Http\Application;

use Amp\Http\Server\Middleware;
use Cspray\AnnotatedContainer\Attribute\Service;
use Labrador\Http\Middleware\Priority;
use Labrador\Http\Router\Router;

#[Service]
interface Application {

    public function getRouter() : Router;

    public function addMiddleware(Middleware $middleware, Priority $priority = Priority::Low) : void;

    public function start() : void;

    public function stop() : void;

}