<?php declare(strict_types=1);

namespace Labrador\Test\Unit\Web\Application\Analytics;

use Amp\Http\HttpStatus;
use Amp\Http\Server\Driver\Client;
use Amp\Http\Server\Request;
use Amp\Http\Server\Response;
use Exception;
use Labrador\Test\Unit\Web\Stub\KnownIncrementPreciseTime;
use Labrador\Test\Unit\Web\Stub\ToStringControllerStub;
use Labrador\Web\Application\Analytics\RequestBenchmark;
use Labrador\Web\Controller\Controller;
use Labrador\Web\HttpMethod;
use Labrador\Web\Router\RoutingResolutionReason;
use League\Uri\Http;
use PHPUnit\Framework\TestCase;

final class RequestBenchmarkTest extends TestCase {

    private Request $request;
    private KnownIncrementPreciseTime $preciseTime;

    private Controller $controller;

    protected function setUp() : void {
        $this->request = new Request(
            $this->getMockBuilder(Client::class)->getMock(),
            HttpMethod::Get->value,
            Http::createFromString('https://example.com')
        );
        $this->preciseTime = new KnownIncrementPreciseTime(
            10, 1
        );
        $this->controller = new ToStringControllerStub('KnownController');
    }

    public function testHappyPathReturnsCorrectAnalytics() : void {
        $subject = RequestBenchmark::requestReceived($this->request, $this->preciseTime);

        $subject->routingStarted();

        $subject->routingCompleted(RoutingResolutionReason::RequestMatched);

        $subject->middlewareProcessingStarted();

        $subject->middlewareProcessingCompleted();

        $subject->controllerProcessingStarted($this->controller);

        $response = new Response();
        $analytics = $subject->responseSent($response);

        self::assertSame($this->request, $analytics->request());
        self::assertSame('KnownController', $analytics->controllerName());
        self::assertSame(RoutingResolutionReason::RequestMatched, $analytics->routingResolutionReason());
        self::assertNull($analytics->thrownException());

        self::assertSame(6, $analytics->totalTimeSpentInNanoSeconds());
        self::assertSame(1, $analytics->timeSpentRoutingInNanoSeconds());
        self::assertSame(1, $analytics->timeSpentProcessingMiddlewareInNanoseconds());
        self::assertSame(1, $analytics->timeSpentProcessingControllerInNanoseconds());

        self::assertSame(HttpStatus::OK, $analytics->responseStatusCode());
    }

    public function testExceptionThrownInRouterReturnsCorrectAnalytics() : void {
        $subject = RequestBenchmark::requestReceived($this->request, $this->preciseTime);

        $subject->routingStarted();

        $exception = new Exception();
        $analytics = $subject->exceptionThrown($exception);

        self::assertSame($this->request, $analytics->request());
        self::assertNull($analytics->controllerName());
        self::assertNull($analytics->routingResolutionReason());
        self::assertSame($exception, $analytics->thrownException());

        self::assertSame(2, $analytics->totalTimeSpentInNanoSeconds());
        self::assertSame(1, $analytics->timeSpentRoutingInNanoSeconds());
        self::assertSame(0, $analytics->timeSpentProcessingMiddlewareInNanoseconds());
        self::assertSame(0, $analytics->timeSpentProcessingControllerInNanoseconds());

        self::assertSame(HttpStatus::INTERNAL_SERVER_ERROR, $analytics->responseStatusCode());
    }

    public function testExceptionThrownInMiddlewareReturnsCorrectAnalytics() : void {
        $subject = RequestBenchmark::requestReceived($this->request, $this->preciseTime);

        $subject->routingStarted();
        $subject->routingCompleted(RoutingResolutionReason::RequestMatched);
        $subject->middlewareProcessingStarted();

        $exception = new Exception();
        $analytics = $subject->exceptionThrown($exception);

        self::assertSame($this->request, $analytics->request());
        self::assertNull($analytics->controllerName());
        self::assertSame(RoutingResolutionReason::RequestMatched, $analytics->routingResolutionReason());
        self::assertSame($exception, $analytics->thrownException());

        self::assertSame(4, $analytics->totalTimeSpentInNanoSeconds());
        self::assertSame(1, $analytics->timeSpentRoutingInNanoSeconds());
        self::assertSame(1, $analytics->timeSpentProcessingMiddlewareInNanoseconds());
        self::assertSame(0, $analytics->timeSpentProcessingControllerInNanoseconds());

        self::assertSame(HttpStatus::INTERNAL_SERVER_ERROR, $analytics->responseStatusCode());
    }

    public function testExceptionThrownInControllerReturnsCorrectAnalysis() : void {
        $subject = RequestBenchmark::requestReceived($this->request, $this->preciseTime);

        $subject->routingStarted();
        $subject->routingCompleted(RoutingResolutionReason::RequestMatched);
        $subject->middlewareProcessingStarted();
        $subject->middlewareProcessingCompleted();
        $subject->controllerProcessingStarted($this->controller);

        $exception = new Exception();
        $analytics = $subject->exceptionThrown($exception);

        self::assertSame($this->request, $analytics->request());
        self::assertSame($this->controller->toString(), $analytics->controllerName());
        self::assertSame(RoutingResolutionReason::RequestMatched, $analytics->routingResolutionReason());
        self::assertSame($exception, $analytics->thrownException());

        self::assertSame(6, $analytics->totalTimeSpentInNanoSeconds());
        self::assertSame(1, $analytics->timeSpentRoutingInNanoSeconds());
        self::assertSame(1, $analytics->timeSpentProcessingMiddlewareInNanoseconds());
        self::assertSame(1, $analytics->timeSpentProcessingControllerInNanoseconds());

        self::assertSame(HttpStatus::INTERNAL_SERVER_ERROR, $analytics->responseStatusCode());
    }


}
