<?php declare(strict_types=1);

namespace Labrador\Http\Application;

use Amp\Http\Server\HttpServer;
use Amp\Http\Server\Middleware\AccessLoggerMiddleware;
use Cspray\AnnotatedContainer\Attribute\ServiceDelegate;
use Labrador\AsyncEvent\EventEmitter;
use Labrador\Http\Application\Analytics\PreciseTime;
use Labrador\Http\Application\Analytics\RequestAnalyticsQueue;
use Labrador\Http\ErrorHandlerFactory;
use Labrador\Http\Logging\LoggerFactory;
use Labrador\Http\Logging\LoggerType;
use Labrador\Http\Middleware\Priority;
use Labrador\Http\Router\Router;
use Psr\Log\LoggerInterface;

final class ApplicationFactory {

    #[ServiceDelegate]
    public static function createApp(
        HttpServer                 $httpServer,
        ErrorHandlerFactory $errorHandlerFactory,
        Router                     $router,
        EventEmitter               $emitter,
        LoggerFactory              $loggerFactory,
        ApplicationFeatures        $features,
        RequestAnalyticsQueue      $analyticsQueue,
        PreciseTime                $preciseTime,
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