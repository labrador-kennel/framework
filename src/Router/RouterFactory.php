<?php

namespace Cspray\Labrador\Http\Router;

use Cspray\AnnotatedContainer\Attribute\ServiceDelegate;
use FastRoute\DataGenerator\GroupCountBased as GcbDataGenerator;
use FastRoute\Dispatcher\GroupCountBased as GcbDispatcher;
use FastRoute\RouteParser\Std as StdRouteParser;
use FastRoute\RouteCollector;

class RouterFactory {

    #[ServiceDelegate]
    public static function createRouter() : Router {
        return new FastRouteRouter(
            new RouteCollector(new StdRouteParser(), new GcbDataGenerator()),
            function(array $data) : GcbDispatcher { return new GcbDispatcher($data); }
        );
    }

}