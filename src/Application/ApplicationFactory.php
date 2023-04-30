<?php declare(strict_types=1);

namespace Labrador\Http\Application;

use Amp\Http\Server\HttpServer;
use Cspray\AnnotatedContainer\Attribute\ServiceDelegate;
use Labrador\AsyncEvent\EventEmitter;
use Labrador\Http\ErrorHandlerFactory;
use Labrador\Http\Logging\LoggerFactory;
use Labrador\Http\Logging\LoggerType;
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
    ) : Application {
        return new AmpApplication(
            $httpServer,
            $errorHandlerFactory,
            $router,
            $emitter,
            $loggerFactory->createLogger(LoggerType::Application),
            $features
        );
    }

}