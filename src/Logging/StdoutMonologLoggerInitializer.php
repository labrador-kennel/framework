<?php declare(strict_types=1);

namespace Labrador\Http\Logging;

use Amp\Log\ConsoleFormatter;
use Amp\Log\StreamHandler;
use Cspray\AnnotatedContainer\Attribute\Service;
use Monolog\Logger;
use function Amp\ByteStream\getStdout;

#[Service]
final class StdoutMonologLoggerInitializer implements MonologLoggerInitializer {

    public function initialize(Logger $logger, LoggerType $loggerType) : void {
        $handler = new StreamHandler(getStdout());
        $handler->setFormatter(new ConsoleFormatter());

        $logger->pushHandler($handler);
    }

}
