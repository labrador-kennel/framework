<?php declare(strict_types=1);

namespace Cspray\Labrador\Http\Plugin;

use Cspray\Labrador\Http\Router\Router;

interface RouterPlugin {

    public function registerRoutes(Router $router) : void;

}