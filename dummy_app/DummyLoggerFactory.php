<?php declare(strict_types=1);

namespace Labrador\HttpDummyApp;

use Cspray\AnnotatedContainer\Attribute\ServiceDelegate;
use Labrador\Http\Logging\LoggerFactory;
use Labrador\Http\Logging\LoggerType;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

final class DummyLoggerFactory {

    #[ServiceDelegate]
    public static function createLogger(LoggerFactory $loggerFactory) : LoggerInterface {
        return $loggerFactory->createLogger(LoggerType::Application);
    }

}