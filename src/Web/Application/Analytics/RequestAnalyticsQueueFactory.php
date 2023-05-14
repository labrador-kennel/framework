<?php declare(strict_types=1);

namespace Labrador\Web\Application\Analytics;

use Cspray\AnnotatedContainer\Attribute\ServiceDelegate;
use Labrador\Logging\LoggerFactory;
use Labrador\Logging\LoggerType;

final class RequestAnalyticsQueueFactory {

    private function __construct() {}

    #[ServiceDelegate]
    public static function createAnalyticsQueue(LoggerFactory $loggerFactory) : RequestAnalyticsQueue {
        return new LoggingRequestAnalyticsQueue(
            $loggerFactory->createLogger(LoggerType::Application)
        );
    }

}