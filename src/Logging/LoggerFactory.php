<?php declare(strict_types=1);

namespace Labrador\Logging;

use Cspray\AnnotatedContainer\Attribute\Service;
use Psr\Log\LoggerInterface;

#[Service]
interface LoggerFactory {

    public function createLogger(LoggerType $loggerType) : LoggerInterface;

}
