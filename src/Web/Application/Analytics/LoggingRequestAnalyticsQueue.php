<?php declare(strict_types=1);

namespace Labrador\Web\Application\Analytics;

use Psr\Log\LoggerInterface;

final class LoggingRequestAnalyticsQueue implements RequestAnalyticsQueue {

    public function __construct(
        private readonly LoggerInterface $logger
    ) {
    }

    public function queue(RequestAnalytics $analytics) : void {
        $exception = $analytics->thrownException();
        if ($exception === null) {
            $this->logger->info(
                'Processed "{request}" in {total_time_spent} nanoseconds.',
                [
                    'request' => sprintf("%s %s", $analytics->request()->getMethod(), $analytics->request()->getUri()->getPath()),
                    'resolution_reason' => $analytics->routingResolutionReason(),
                    'request_handler' => $analytics->requestHandlerName(),
                    'total_time_spent' => $analytics->totalTimeSpentInNanoSeconds(),
                    'time_spent_routing' => $analytics->timeSpentRoutingInNanoSeconds(),
                    'time_spent_middleware' => $analytics->timeSpentProcessingMiddlewareInNanoseconds(),
                    'time_spent_request_handler' => $analytics->timeSpentProcessingRequestHandlerInNanoseconds(),
                    'response_code' => $analytics->responseStatusCode()
                ]
            );
        } else {
            $this->logger->info(
                'Failed processing "{request}" in {total_time_spent} nanoseconds.',
                [
                    'request' => sprintf("%s %s", $analytics->request()->getMethod(), $analytics->request()->getUri()->getPath()),
                    'resolution_reason' => $analytics->routingResolutionReason(),
                    'request_handler' => $analytics->requestHandlerName(),
                    'total_time_spent' => $analytics->totalTimeSpentInNanoSeconds(),
                    'time_spent_routing' => $analytics->timeSpentRoutingInNanoSeconds(),
                    'time_spent_middleware' => $analytics->timeSpentProcessingMiddlewareInNanoseconds(),
                    'time_spent_request_handler' => $analytics->timeSpentProcessingRequestHandlerInNanoseconds(),
                    'response_code' => $analytics->responseStatusCode(),
                    'exception_message' => $exception->getMessage(),
                    'exception_class' => $exception::class,
                    'exception_file' => $exception->getFile(),
                    'exception_line' => $exception->getLine()
                ]
            );
        }
    }
}
