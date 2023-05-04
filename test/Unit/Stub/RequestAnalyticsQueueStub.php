<?php declare(strict_types=1);

namespace Labrador\Http\Test\Unit\Stub;

use Labrador\Http\Application\Analytics\RequestAnalytics;
use Labrador\Http\Application\Analytics\RequestAnalyticsQueue;

final class RequestAnalyticsQueueStub implements RequestAnalyticsQueue {

    private $queue = [];

    public function queue(RequestAnalytics $analytics) : void {
        $this->queue[] = $analytics;
    }

    public function getQueuedRequestAnalytics() : array {
        return $this->queue;
    }

}
