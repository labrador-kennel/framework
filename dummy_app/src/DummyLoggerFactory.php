<?php declare(strict_types=1);

namespace Labrador\DummyApp;

use Cspray\AnnotatedContainer\Attribute\Service;
use Cspray\AnnotatedContainer\Attribute\ServiceDelegate;
use Labrador\Logging\LoggerFactory;
use Monolog\Handler\TestHandler;
use Monolog\Logger;
use Monolog\Processor\PsrLogMessageProcessor;
use Psr\Log\LoggerInterface;

#[Service]
final class DummyLoggerFactory implements LoggerFactory {

    public readonly TestHandler $testHandler;

    public function __construct() {
        $this->testHandler = new TestHandler();
    }

    #[ServiceDelegate]
    public function createLogger() : LoggerInterface {
        return new Logger('labrador.app', [$this->testHandler], [new PsrLogMessageProcessor()]);
    }
}