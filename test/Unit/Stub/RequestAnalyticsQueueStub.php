<?php declare(strict_types=1);

namespace Labrador\Test\Unit\Stub;

use Labrador\Web\Application\Analytics\RequestAnalytics;
use Labrador\Web\Application\Analytics\RequestAnalyticsQueue;

final class RequestAnalyticsQueueStub implements RequestAnalyticsQueue {

    private $queue = [];

    public function queue(RequestAnalytics $analytics) : void {
        $this->queue[] = $analytics;
    }

    public function getQueuedRequestAnalytics() : array {
        return $this->queue;
    }

}
