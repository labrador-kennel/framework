<?php

namespace Labrador\Web\Controller;

use Amp\Http\Server\Middleware;
use Labrador\Web\Router\RequestMapping;

interface RouteMappingAttribute {

    public function getRequestMapping() : RequestMapping;

    /**
     * @return list<class-string<Middleware>>
     */
    public function getMiddleware() : array;

}