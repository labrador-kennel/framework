<?php declare(strict_types=1);

namespace Labrador\Http\Logging;

use Cspray\AnnotatedContainer\Attribute\Service;
use Monolog\Logger;
use Monolog\Processor\PsrLogMessageProcessor;
use Psr\Log\LoggerInterface;

#[Service]
final class MonologLoggerFactory implements LoggerFactory {

    /**
     * @var array<non-empty-string, LoggerInterface>
     */
    private array $cache = [];

    public function __construct(
        private readonly MonologLoggerInitializer $loggerInitializer
    ) {}

    public function createLogger(LoggerType $loggerType) : LoggerInterface {
        if (!array_key_exists($loggerType->value, $this->cache)) {
            $logger = new Logger($loggerType->value);
            $logger->pushProcessor(new PsrLogMessageProcessor());

            $this->loggerInitializer->initialize($logger, $loggerType);

            $this->cache[$loggerType->value] = $logger;
        }

        return $this->cache[$loggerType->value];
    }

}
