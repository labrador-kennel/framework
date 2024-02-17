<?php declare(strict_types=1);

namespace Labrador\DummyApp;

use Cspray\AnnotatedContainer\Attribute\Service;
use Labrador\Logging\LoggerType;
use Labrador\Logging\MonologLoggerInitializer;
use Monolog\Handler\TestHandler;
use Monolog\Logger;
use Monolog\Processor\PsrLogMessageProcessor;

#[Service]
class DummyMonologInitializer implements MonologLoggerInitializer {

    public function __construct(
        public readonly TestHandler $testHandler
    ) {}

    public function initialize(Logger $logger, LoggerType $loggerType) : void {
        $logger->pushProcessor(new PsrLogMessageProcessor());
        $logger->pushHandler($this->testHandler);
    }

}