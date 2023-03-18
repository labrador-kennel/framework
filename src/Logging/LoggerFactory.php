<?php

namespace Labrador\Http\Logging;

use Amp\Log\ConsoleFormatter;
use Amp\Log\StreamHandler;
use Cspray\AnnotatedContainer\Attribute\ServiceDelegate;
use Cspray\AnnotatedContainer\Profiles\ActiveProfiles;
use Monolog\Logger;
use Monolog\Processor\PsrLogMessageProcessor;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use function Amp\ByteStream\getStdout;

class LoggerFactory {

    #[ServiceDelegate]
    public static function createLogger() : LoggerInterface {
        $logger = new Logger('labrador-http');
        $logger->pushProcessor(new PsrLogMessageProcessor());

        $handler = new StreamHandler(getStdout());
        $handler->setFormatter(new ConsoleFormatter());

        $logger->pushHandler($handler);

        return $logger;
    }

}
