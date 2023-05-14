<?php declare(strict_types=1);

namespace Labrador\DummyApp;

use Amp\Log\StreamHandler;
use Cspray\AnnotatedContainer\Attribute\ServiceDelegate;
use Labrador\Logging\LoggerFactory;
use Monolog\Logger;
use Monolog\Processor\PsrLogMessageProcessor;
use Psr\Log\LoggerInterface;
use function Amp\ByteStream\getStdout;

final class DummyLoggerFactory {

    #[ServiceDelegate]
    public static function createLogger() : LoggerInterface {
        $handler = new StreamHandler(getStdout());
        return new Logger(
            'dummy-app',
            [$handler],
            [new PsrLogMessageProcessor()]
        );
    }

}