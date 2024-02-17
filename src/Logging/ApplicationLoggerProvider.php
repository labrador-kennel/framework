<?php declare(strict_types=1);

namespace Labrador\Logging;

use Cspray\AnnotatedContainer\Attribute\ServiceDelegate;
use Psr\Log\LoggerInterface;

class ApplicationLoggerProvider {

    #[ServiceDelegate]
    public static function createLogger(LoggerFactory $loggerFactory) : LoggerInterface {
        return $loggerFactory->createLogger(LoggerType::Application);
    }

}
