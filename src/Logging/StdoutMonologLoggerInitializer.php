<?php declare(strict_types=1);

namespace Labrador\Logging;

use Amp\Log\ConsoleFormatter;
use Amp\Log\StreamHandler;
use Cspray\AnnotatedContainer\Attribute\Service;
use Monolog\Logger;
use Psr\Log\LogLevel;
use function Amp\ByteStream\getStdout;

#[Service]
final class StdoutMonologLoggerInitializer implements MonologLoggerInitializer {

    public function initialize(Logger $logger, LoggerType $loggerType) : void {
        $logLevel = LogLevel::DEBUG;
        if ($loggerType === LoggerType::WebServer) {
            $logLevel = LogLevel::INFO;
        }
        $handler = new StreamHandler(getStdout(), $logLevel);
        $handler->setFormatter(new ConsoleFormatter());

        $logger->pushHandler($handler);
    }

}
