<?php declare(strict_types=1);

namespace Labrador\Http\Application\Analytics;

use Psr\Log\LoggerInterface;

class LoggingRequestAnalyticsQueue implements RequestAnalyticsQueue {

    public function __construct(
        private readonly LoggerInterface $logger
    ) {}

    public function queue(RequestAnalytics $analytics) : void {
        $exception = $analytics->getThrownException();
        if ($exception === null) {
            $this->logger->info(
                'Processed "{request}" in {total_time_spent} nanoseconds.',
                [
                    'request' => sprintf("%s %s", $analytics->getRequest()->getMethod(), $analytics->getRequest()->getUri()->getPath()),
                    'resolution_reason' => $analytics->getRoutingResolutionReason(),
                    'controller' => $analytics->getControllerName(),
                    'total_time_spent' => $analytics->getTotalTimeSpentInNanoSeconds(),
                    'time_spent_routing' => $analytics->getTimeSpentRoutingInNanoSeconds(),
                    'time_spent_middleware' => $analytics->getTimeSpentProcessingMiddlewareInNanoseconds(),
                    'time_spent_controller' => $analytics->getTimeSpentProcessingControllerInNanoseconds(),
                    'response_code' => $analytics->getResponseStatusCode()
                ]
            );
        } else {
            $this->logger->info(
                'Failed processing "{request}" in {total_time_spent} nanoseconds.',
                [
                    'request' => sprintf("%s %s", $analytics->getRequest()->getMethod(), $analytics->getRequest()->getUri()->getPath()),
                    'resolution_reason' => $analytics->getRoutingResolutionReason(),
                    'controller' => $analytics->getControllerName(),
                    'total_time_spent' => $analytics->getTotalTimeSpentInNanoSeconds(),
                    'time_spent_routing' => $analytics->getTimeSpentRoutingInNanoSeconds(),
                    'time_spent_middleware' => $analytics->getTimeSpentProcessingMiddlewareInNanoseconds(),
                    'time_spent_controller' => $analytics->getTimeSpentProcessingControllerInNanoseconds(),
                    'response_code' => $analytics->getResponseStatusCode(),
                    'exception_message' => $exception->getMessage(),
                    'exception_class' => $exception::class,
                    'exception_file' => $exception->getFile(),
                    'exception_line' => $exception->getLine()
                ]
            );
        }
    }
}