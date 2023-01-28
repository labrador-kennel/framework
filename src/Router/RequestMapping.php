<?php

namespace Labrador\Http\Router;

use Labrador\Http\HttpMethod;

interface RequestMapping {

    public function getHttpMethod() : HttpMethod;

    public function getPath() : string;

    public function withPath(string $path) : RequestMapping;

}