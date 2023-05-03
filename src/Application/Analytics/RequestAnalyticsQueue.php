<?php declare(strict_types=1);

namespace Labrador\Http\Application\Analytics;

interface RequestAnalyticsQueue {

    public function queue(RequestAnalytics $analytics) : void;

}
