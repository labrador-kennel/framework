<?php declare(strict_types=1);

namespace Labrador\Http\Logging;

use Cspray\AnnotatedContainer\Attribute\Service;
use Monolog\Logger;
use Monolog\Processor\PsrLogMessageProcessor;
use Psr\Log\LoggerInterface;

#[Service]
final class MonologLoggerFactory implements LoggerFactory {

    public function __construct(
        private readonly MonologLoggerInitializer $loggerInitializer
    ) {}

    public function createLogger(LoggerType $loggerType) : LoggerInterface {
        $logger = new Logger($loggerType->value);
        $logger->pushProcessor(new PsrLogMessageProcessor());

        $this->loggerInitializer->initialize($logger, $loggerType);

        return $logger;
    }

}
