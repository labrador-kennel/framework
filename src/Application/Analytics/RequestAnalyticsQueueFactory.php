<?php declare(strict_types=1);

namespace Labrador\Http\Application\Analytics;

use Cspray\AnnotatedContainer\Attribute\ServiceDelegate;
use Labrador\Http\Logging\LoggerFactory;
use Labrador\Http\Logging\LoggerType;

final class RequestAnalyticsQueueFactory {

    private function __construct() {}

    #[ServiceDelegate]
    public static function createAnalyticsQueue(LoggerFactory $loggerFactory) : RequestAnalyticsQueue {
        return new LoggingRequestAnalyticsQueue(
            $loggerFactory->createLogger(LoggerType::Application)
        );
    }

}