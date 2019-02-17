<?php declare(strict_types=1);

namespace Cspray\Labrador\Http\Plugin;

use Cspray\Labrador\Http\Router\Router;
use Cspray\Labrador\Plugin\Plugin;

interface RouterPlugin extends Plugin {

    public function registerRoutes(Router $router) : void;
}
