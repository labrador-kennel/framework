<?php declare(strict_types=1);

namespace Labrador\Web\Application\Analytics;

use Amp\Http\Server\Request;
use Labrador\Web\Router\RoutingResolutionReason;

interface RequestAnalytics {

    public function request() : Request;

    public function routingResolutionReason() : ?RoutingResolutionReason;

    public function controllerName() : ?string;

    public function thrownException() : ?\Throwable;

    public function totalTimeSpentInNanoSeconds() : int|float;

    public function timeSpentRoutingInNanoSeconds() : int|float;

    public function timeSpentProcessingMiddlewareInNanoseconds() : int|float;

    public function timeSpentProcessingControllerInNanoseconds() : int|float;

    public function responseStatusCode() : int;
}
