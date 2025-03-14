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
use Labrador\Web\Middleware\Priority;
use Labrador\Web\Router\Router;

final class ApplicationFactory {

    #[ServiceDelegate]
    public static function createApp(
        HttpServer            $httpServer,
        ErrorHandlerFactory   $errorHandlerFactory,
        Router                $router,
        Emitter               $emitter,
        LoggerFactory         $loggerFactory,
        ApplicationSettings   $features,
        RequestAnalyticsQueue $analyticsQueue,
        PreciseTime           $preciseTime,
    ) : Application {
        $app = new AmpApplication(
            $httpServer,
            $errorHandlerFactory,
            $router,
            $emitter,
            $loggerFactory->createLogger(LoggerType::Application),
            $features,
            $analyticsQueue,
            $preciseTime
        );

        $accessLoggingMiddleware = new AccessLoggerMiddleware(
            $loggerFactory->createLogger(LoggerType::WebServer)
        );

        $app->addMiddleware($accessLoggingMiddleware, Priority::Critical);

        return $app;
    }
}
