<?php

namespace Labrador\Web\Controller;

use Amp\Http\Server\Middleware;
use Labrador\Web\Router\Mapping\RequestMapping;

interface RouteMappingAttribute {

    public function requestMapping() : RequestMapping;

    /**
     * @return list<class-string<Middleware>>
     */
    public function middleware() : array;

}