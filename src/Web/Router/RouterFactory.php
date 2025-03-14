<?php

namespace Labrador\Web\Router;

use Cspray\AnnotatedContainer\Attribute\ServiceDelegate;
use FastRoute\DataGenerator\GroupCountBased as GcbDataGenerator;
use FastRoute\Dispatcher\GroupCountBased as GcbDispatcher;
use FastRoute\RouteCollector;
use FastRoute\RouteParser\Std as StdRouteParser;
use Labrador\Logging\LoggerFactory;
use Labrador\Logging\LoggerType;

final class RouterFactory {

    #[ServiceDelegate]
    public static function createRouter(LoggerFactory $loggerFactory) : Router {
        $logger = $loggerFactory->createLogger(LoggerType::Router);
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
