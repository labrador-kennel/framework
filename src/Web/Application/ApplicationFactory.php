<?php declare(strict_types=1);

namespace Labrador\Web\Application;

use Amp\Http\Server\ErrorHandler;
use Amp\Http\Server\HttpServer;
use Cspray\AnnotatedContainer\Attribute\ServiceDelegate;
use Labrador\AsyncEvent\Emitter;
use Labrador\Web\Application\Analytics\PreciseTime;
use Labrador\Web\Application\Analytics\RequestAnalyticsQueue;
use Labrador\Web\Middleware\GlobalMiddlewareCollection;
use Labrador\Web\Router\Router;
use Psr\Log\LoggerInterface;

final class ApplicationFactory {

    #[ServiceDelegate]
    public static function createApp(
        HttpServer            $httpServer,
        ErrorHandler   $errorHandler,
        Router                $router,
        Emitter               $emitter,
        LoggerInterface $logger,
        RequestAnalyticsQueue $analyticsQueue,
        PreciseTime           $preciseTime,
    ) : Application {
        return new AmpApplication(
            $httpServer,
            $errorHandler,
            $router,
            new GlobalMiddlewareCollection(),
            $emitter,
            $logger,
            $analyticsQueue,
            $preciseTime
        );
    }
}
