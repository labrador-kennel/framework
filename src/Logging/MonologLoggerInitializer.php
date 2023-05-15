<?php declare(strict_types=1);

namespace Labrador\Logging;

use Cspray\AnnotatedContainer\Attribute\Service;
use Monolog\Logger;

#[Service]
interface MonologLoggerInitializer {

    public function initialize(Logger $logger, LoggerType $loggerType) : void;

}