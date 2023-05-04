<?php declare(strict_types=1);

namespace Labrador\Http\Application\Analytics;

use Amp\Http\Server\Request;
use Labrador\Http\Router\RoutingResolutionReason;

interface RequestAnalytics {

    public function getRequest() : Request;

    public function getRoutingResolutionReason() : ?RoutingResolutionReason;

    public function getControllerName() : ?string;

    public function getThrownException() : ?\Throwable;

    public function getTotalTimeSpentInNanoSeconds() : int|float;

    public function getTimeSpentRoutingInNanoSeconds() : int|float;

    public function getTimeSpentProcessingMiddlewareInNanoseconds() : int|float;

    public function getTimeSpentProcessingControllerInNanoseconds() : int|float;

    public function getResponseStatusCode() : int;

}
