<?php declare(strict_types=1);

namespace Labrador\Web\Application\Analytics;

use Amp\Http\HttpStatus;
use Amp\Http\Server\Request;
use Amp\Http\Server\RequestHandler;
use Amp\Http\Server\Response;
use Labrador\Web\Router\RoutingResolutionReason;
use Throwable;

final class RequestBenchmark {

    private readonly int|float $startTime;

    private int|float|null $routingStarted = null;

    private int|float|null $routingCompleted = null;

    private ?RoutingResolutionReason $routingResolutionReason = null;

    private int|float|null $middlewareStarted = null;

    private int|float|null $middlewareCompleted = null;

    private int|float|null $requestHandlerStarted = null;

    private ?string $requestHandlerName = null;

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

    public function requestHandlerProcessingStarted(RequestHandler $requestHandler) : void {
        $this->requestHandlerStarted = $this->preciseTime->now();
        $this->requestHandlerName = $requestHandler::class;
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
        if (isset($this->requestHandlerStarted)) {
            $timeSpentRequestHandler = $finishTime - $this->requestHandlerStarted;
        } else {
            $timeSpentRequestHandler = 0;
        }

        return new class(
            $this->request,
            $throwable,
            $this->routingResolutionReason ?? null,
            $this->requestHandlerName ?? null,
            $totalTimeSpent,
            $timeSpentRouting,
            $timeSpentMiddleware,
            $timeSpentRequestHandler
        ) implements RequestAnalytics {

            public function __construct(
                private readonly Request $request,
                private readonly Throwable $throwable,
                private readonly ?RoutingResolutionReason $resolutionReason,
                private readonly ?string $requestHandlerName,
                private readonly int|float $totalTimeSpent,
                private readonly int|float $totalTimeRouting,
                private readonly int|float $totalTimeMiddleware,
                private readonly int|float $totalTimeRequestHandler
            ) {
            }

            public function request() : Request {
                return $this->request;
            }

            public function routingResolutionReason() : ?RoutingResolutionReason {
                return $this->resolutionReason;
            }

            public function requestHandlerName() : ?string {
                return $this->requestHandlerName;
            }

            public function thrownException() : Throwable {
                return $this->throwable;
            }

            public function totalTimeSpentInNanoSeconds() : int|float {
                return $this->totalTimeSpent;
            }

            public function timeSpentRoutingInNanoSeconds() : int|float {
                return $this->totalTimeRouting;
            }

            public function timeSpentProcessingMiddlewareInNanoseconds() : int|float {
                return $this->totalTimeMiddleware;
            }

            public function timeSpentProcessingRequestHandlerInNanoseconds() : int|float {
                return $this->totalTimeRequestHandler;
            }

            public function responseStatusCode() : int {
                return HttpStatus::INTERNAL_SERVER_ERROR;
            }
        };
    }

    public function responseSent(Response $response) : RequestAnalytics {
        $finishTime = $this->preciseTime->now();
        $totalTimeSpent = $finishTime - $this->startTime;
        $timeSpentRouting = $this->routingCompleted - $this->routingStarted;
        $timeSpentProcessingMiddleware = $this->middlewareCompleted - $this->middlewareStarted;
        if (isset($this->requestHandlerStarted)) {
            $timeSpentProcessingRequestHandler = $finishTime - $this->requestHandlerStarted;
        } else {
            $timeSpentProcessingRequestHandler = 0;
        }
        return new class(
            $this->request,
            $this->requestHandlerName,
            $totalTimeSpent,
            $timeSpentRouting,
            $this->routingResolutionReason,
            $timeSpentProcessingMiddleware,
            $timeSpentProcessingRequestHandler,
            $response->getStatus(),
        ) implements RequestAnalytics {

            public function __construct(
                private readonly Request $request,
                private readonly ?string $requestHandlerName,
                private readonly int|float $totalTimeSpent,
                private readonly int|float $timeSpentRouting,
                private readonly ?RoutingResolutionReason $resolutionReason,
                private readonly int|float $timeSpentProcessingMiddleware,
                private readonly int|float $timeSpentProcessingRequestHandler,
                private readonly int $responseStatusCode,
            ) {
            }

            public function request() : Request {
                return $this->request;
            }

            public function routingResolutionReason() : ?RoutingResolutionReason {
                return $this->resolutionReason;
            }

            public function requestHandlerName() : ?string {
                return $this->requestHandlerName;
            }

            public function thrownException() : ?Throwable {
                return null;
            }

            public function totalTimeSpentInNanoSeconds() : int|float {
                return $this->totalTimeSpent;
            }

            public function timeSpentRoutingInNanoSeconds() : int|float {
                return $this->timeSpentRouting;
            }

            public function timeSpentProcessingMiddlewareInNanoseconds() : int|float {
                return $this->timeSpentProcessingMiddleware;
            }

            public function timeSpentProcessingRequestHandlerInNanoseconds() : int|float {
                return $this->timeSpentProcessingRequestHandler;
            }

            public function responseStatusCode() : int {
                return $this->responseStatusCode;
            }
        };
    }
}
