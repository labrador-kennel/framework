<?php

namespace Labrador\Http\Controller;

use Amp\Http\Server\Middleware;
use Labrador\Http\HttpMethod;

interface RouteMappingAttribute {

    public function getHttpMethod() : HttpMethod;

    public function getPath() : string;

    /**
     * @return list<class-string<Middleware>>
     */
    public function getMiddleware() : array;

}