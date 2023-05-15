<?php

namespace Labrador\Web\Router;

use Labrador\Web\HttpMethod;

interface RequestMapping {

    public function getHttpMethod() : HttpMethod;

    public function getPath() : string;

    public function withPath(string $path) : RequestMapping;

}