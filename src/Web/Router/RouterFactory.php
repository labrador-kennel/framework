<?php

namespace Labrador\Web\Router;

use Cspray\AnnotatedContainer\Attribute\ServiceDelegate;
use FastRoute\DataGenerator\GroupCountBased as GcbDataGenerator;
use FastRoute\Dispatcher\GroupCountBased as GcbDispatcher;
use FastRoute\RouteCollector;
use FastRoute\RouteParser\Std as StdRouteParser;
use Psr\Log\LoggerInterface;

final class RouterFactory {

    #[ServiceDelegate]
    public static function createRouter(LoggerInterface $logger) : Router {
        return new LoggingRouter(
            new FastRouteRouter(
                new RouteCollector(new StdRouteParser(), new GcbDataGenerator()),
                function(array $data) : GcbDispatcher { return new GcbDispatcher($data);
                }
            ),
            $logger
        );
    }
}
