<?php declare(strict_types=1);

namespace Labrador\Logging;

use Cspray\AnnotatedContainer\Attribute\Service;
use Cspray\AnnotatedContainer\Attribute\ServiceDelegate;
use Psr\Log\LoggerInterface;

#[Service]
interface LoggerFactory {

    #[ServiceDelegate]
    public function createLogger() : LoggerInterface;
}
