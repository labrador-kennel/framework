<?php declare(strict_types=1);

namespace Labrador\Web\Application\Analytics;

use Cspray\AnnotatedContainer\Attribute\ServiceDelegate;
use Labrador\Logging\LoggerFactory;
use Labrador\Logging\LoggerType;
use Psr\Log\LoggerInterface;

final class RequestAnalyticsQueueFactory {

    private function __construct() {
    }

    #[ServiceDelegate]
    public static function createAnalyticsQueue(LoggerInterface $logger) : RequestAnalyticsQueue {
        return new LoggingRequestAnalyticsQueue($logger);
    }
}
