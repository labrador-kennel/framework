<?php declare(strict_types=1);

namespace Labrador\Web\Application;

use Amp\Http\Server\HttpServer;
use Amp\Http\Server\Middleware\AccessLoggerMiddleware;
use Cspray\AnnotatedContainer\Attribute\ServiceDelegate;
use Labrador\AsyncEvent\Emitter;
use Labrador\Logging\LoggerFactory;
use Labrador\Logging\LoggerType;
use Labrador\Web\Application\Analytics\PreciseTime;
use Labrador\Web\Application\Analytics\RequestAnalyticsQueue;
use Labrador\Web\Middleware\GlobalMiddlewareCollection;
use Labrador\Web\Middleware\Priority;
use Labrador\Web\Router\Router;
use Psr\Log\LoggerInterface;

final class ApplicationFactory {

    #[ServiceDelegate]
    public static function createApp(
        HttpServer            $httpServer,
        ErrorHandlerFactory   $errorHandlerFactory,
        Router                $router,
        Emitter               $emitter,
        LoggerInterface $logger,
        ApplicationSettings   $features,
        RequestAnalyticsQueue $analyticsQueue,
        PreciseTime           $preciseTime,
    ) : Application {
        return new AmpApplication(
            $httpServer,
            $errorHandlerFactory,
            $router,
            new GlobalMiddlewareCollection(),
            $emitter,
            $logger,
            $features,
            $analyticsQueue,
            $preciseTime
        );
    }
}
