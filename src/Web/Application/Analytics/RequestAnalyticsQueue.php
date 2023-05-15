<?php declare(strict_types=1);

namespace Labrador\Web\Application\Analytics;

use Cspray\AnnotatedContainer\Attribute\Service;

#[Service]
interface RequestAnalyticsQueue {

    public function queue(RequestAnalytics $analytics) : void;

}
