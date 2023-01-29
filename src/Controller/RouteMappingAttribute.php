<?php

namespace Labrador\Http\Controller;

use Amp\Http\Server\Middleware;
use Labrador\Http\HttpMethod;
use Labrador\Http\Router\RequestMapping;

interface RouteMappingAttribute {

    public function getRequestMapping() : RequestMapping;

    /**
     * @return list<class-string<Middleware>>
     */
    public function getMiddleware() : array;

}