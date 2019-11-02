<?php declare(strict_types=1);

namespace Cspray\Labrador\Http\Test\Stub;

use Cspray\Labrador\Http\Plugin\RouterPlugin;
use Cspray\Labrador\Http\Router\Router;

class TestRouterPlugin implements RouterPlugin {

    public $router;

    public function registerRoutes(Router $router) : void {
        $this->router = $router;
    }
}
