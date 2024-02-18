<?php declare(strict_types=1);

namespace Labrador\Web\Application\Analytics;

use Amp\Http\HttpStatus;
use Amp\Http\Server\Request;
use Amp\Http\Server\Response;
use Labrador\Web\Controller\Controller;
use Labrador\Web\Router\RoutingResolutionReason;
use Throwable;

final class RequestBenchmark {

    private readonly int|float $startTime;

    private int|float|null $routingStarted = null;

    private int|float|null $routingCompleted = null;

    private ?RoutingResolutionReason $routingResolutionReason = null;

    private int|float|null $middlewareStarted = null;

    private int|float|null $middlewareCompleted = null;

    private int|float|null $controllerStarted = null;

    private ?string $controllerName = null;

    private function __construct(
        private readonly Request $request,
        private readonly PreciseTime $preciseTime,
    ) {
        $this->startTime = $this->preciseTime->now();
    }

    public static function requestReceived(Request $request, PreciseTime $preciseTime) : self {
        return new RequestBenchmark($request, $preciseTime);
    }

    public function routingStarted() : void {
        $this->routingStarted = $this->preciseTime->now();
    }

    public function routingCompleted(RoutingResolutionReason $resolutionReason) : void {
        $this->routingCompleted = $this->preciseTime->now();
        $this->routingResolutionReason = $resolutionReason;
    }

    public function middlewareProcessingStarted() : void {
        $this->middlewareStarted = $this->preciseTime->now();
    }

    public function middlewareProcessingCompleted() : void {
        $this->middlewareCompleted = $this->preciseTime->now();
    }

    public function controllerProcessingStarted(Controller $controller) : void {
        $this->controllerStarted = $this->preciseTime->now();
        $this->controllerName = $controller->toString();
    }

    public function exceptionThrown(Throwable $throwable) : RequestAnalytics {
        $finishTime = $this->preciseTime->now();
        $totalTimeSpent = $finishTime - $this->startTime;

        if (isset($this->routingStarted)) {
            $timeSpentRouting = ($this->routingCompleted ?? $finishTime) - $this->routingStarted;
        } else {
            $timeSpentRouting = 0;
        }

        if (isset($this->middlewareStarted)) {
            $timeSpentMiddleware = ($this->middlewareCompleted ?? $finishTime) - $this->middlewareStarted;
        } else {
            $timeSpentMiddleware = 0;
        }
        if (isset($this->controllerStarted)) {
            $timeSpentController = $finishTime - $this->controllerStarted;
        } else {
            $timeSpentController = 0;
        }

        return new class(
            $this->request,
            $throwable,
            $this->routingResolutionReason ?? null,
            $this->controllerName ?? null,
            $totalTimeSpent,
            $timeSpentRouting,
            $timeSpentMiddleware,
            $timeSpentController
        ) implements RequestAnalytics {

            public function __construct(
                private readonly Request $request,
                private readonly Throwable $throwable,
                private readonly ?RoutingResolutionReason $resolutionReason,
                private readonly ?string $controllerName,
                private readonly int|float $totalTimeSpent,
                private readonly int|float $totalTimeRouting,
                private readonly int|float $totalTimeMiddleware,
                private readonly int|float $totalTimeController
            ) {}

            public function getRequest() : Request {
                return $this->request;
            }

            public function getRoutingResolutionReason() : ?RoutingResolutionReason {
                return $this->resolutionReason;
            }

            public function getControllerName() : ?string {
                return $this->controllerName;
            }

            public function getThrownException() : Throwable {
                return $this->throwable;
            }

            public function getTotalTimeSpentInNanoSeconds() : int|float {
                return $this->totalTimeSpent;
            }

            public function getTimeSpentRoutingInNanoSeconds() : int|float {
                return $this->totalTimeRouting;
            }

            public function getTimeSpentProcessingMiddlewareInNanoseconds() : int|float {
                return $this->totalTimeMiddleware;
            }

            public function getTimeSpentProcessingControllerInNanoseconds() : int|float {
                return $this->totalTimeController;
            }

            public function getResponseStatusCode() : int {
                return HttpStatus::INTERNAL_SERVER_ERROR;
            }
        };
    }

    public function responseSent(Response $response) : RequestAnalytics {
        $finishTime = $this->preciseTime->now();
        $totalTimeSpent = $finishTime - $this->startTime;
        $timeSpentRouting = $this->routingCompleted - $this->routingStarted;
        $timeSpentProcessingMiddleware = $this->middlewareCompleted - $this->middlewareStarted;
        if (isset($this->controllerStarted)) {
            $timeSpentProcessingController = $finishTime - $this->controllerStarted;
        } else {
            $timeSpentProcessingController = 0;
        }
        return new class(
            $this->request,
            $this->controllerName,
            $totalTimeSpent,
            $timeSpentRouting,
            $this->routingResolutionReason,
            $timeSpentProcessingMiddleware,
            $timeSpentProcessingController,
            $response->getStatus(),
        ) implements RequestAnalytics {

            public function __construct(
                private readonly Request $request,
                private readonly ?string $controllerName,
                private readonly int|float $totalTimeSpent,
                private readonly int|float $timeSpentRouting,
                private readonly ?RoutingResolutionReason $resolutionReason,
                private readonly int|float $timeSpentProcessingMiddleware,
                private readonly int|float $timeSpentProcessingController,
                private readonly int $responseStatusCode,
            ) {}

            public function getRequest() : Request {
                return $this->request;
            }

            public function getRoutingResolutionReason() : ?RoutingResolutionReason {
                return $this->resolutionReason;
            }

            public function getControllerName() : ?string {
                return $this->controllerName;
            }

            public function getThrownException() : ?Throwable {
                return null;
            }

            public function getTotalTimeSpentInNanoSeconds() : int|float {
                return $this->totalTimeSpent;
            }

            public function getTimeSpentRoutingInNanoSeconds() : int|float {
                return $this->timeSpentRouting;
            }

            public function getTimeSpentProcessingMiddlewareInNanoseconds() : int|float {
                return $this->timeSpentProcessingMiddleware;
            }

            public function getTimeSpentProcessingControllerInNanoseconds() : int|float {
                return $this->timeSpentProcessingController;
            }

            public function getResponseStatusCode() : int {
                return $this->responseStatusCode;
            }
        };
    }

}