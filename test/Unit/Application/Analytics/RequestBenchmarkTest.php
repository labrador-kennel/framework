<?php declare(strict_types=1);

namespace Labrador\Http\Test\Unit\Application\Analytics;

use Amp\Http\HttpStatus;
use Amp\Http\Server\Driver\Client;
use Amp\Http\Server\Request;
use Amp\Http\Server\Response;
use Exception;
use Labrador\Http\Application\Analytics\RequestBenchmark;
use Labrador\Http\Controller\Controller;
use Labrador\Http\HttpMethod;
use Labrador\Http\Router\RoutingResolution;
use Labrador\Http\Router\RoutingResolutionReason;
use Labrador\Http\Test\Unit\Stub\KnownIncrementPreciseTime;
use Labrador\Http\Test\Unit\Stub\ToStringControllerStub;
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

        self::assertSame($this->request, $analytics->getRequest());
        self::assertSame('KnownController', $analytics->getControllerName());
        self::assertSame(RoutingResolutionReason::RequestMatched, $analytics->getRoutingResolutionReason());
        self::assertNull($analytics->getThrownException());

        self::assertSame(6, $analytics->getTotalTimeSpentInNanoSeconds());
        self::assertSame(1, $analytics->getTimeSpentRoutingInNanoSeconds());
        self::assertSame(1, $analytics->getTimeSpentProcessingMiddlewareInNanoseconds());
        self::assertSame(1, $analytics->getTimeSpentProcessingControllerInNanoseconds());

        self::assertSame(HttpStatus::OK, $analytics->getResponseStatusCode());
    }

    public function testExceptionThrownInRouterReturnsCorrectAnalytics() : void {
        $subject = RequestBenchmark::requestReceived($this->request, $this->preciseTime);

        $subject->routingStarted();

        $exception = new Exception();
        $analytics = $subject->exceptionThrown($exception);

        self::assertSame($this->request, $analytics->getRequest());
        self::assertNull($analytics->getControllerName());
        self::assertNull($analytics->getRoutingResolutionReason());
        self::assertSame($exception, $analytics->getThrownException());

        self::assertSame(2, $analytics->getTotalTimeSpentInNanoSeconds());
        self::assertSame(1, $analytics->getTimeSpentRoutingInNanoSeconds());
        self::assertSame(0, $analytics->getTimeSpentProcessingMiddlewareInNanoseconds());
        self::assertSame(0, $analytics->getTimeSpentProcessingControllerInNanoseconds());

        self::assertSame(HttpStatus::INTERNAL_SERVER_ERROR, $analytics->getResponseStatusCode());
    }

    public function testExceptionThrownInMiddlewareReturnsCorrectAnalytics() : void {
        $subject = RequestBenchmark::requestReceived($this->request, $this->preciseTime);

        $subject->routingStarted();
        $subject->routingCompleted(RoutingResolutionReason::RequestMatched);
        $subject->middlewareProcessingStarted();

        $exception = new Exception();
        $analytics = $subject->exceptionThrown($exception);

        self::assertSame($this->request, $analytics->getRequest());
        self::assertNull($analytics->getControllerName());
        self::assertSame(RoutingResolutionReason::RequestMatched, $analytics->getRoutingResolutionReason());
        self::assertSame($exception, $analytics->getThrownException());

        self::assertSame(4, $analytics->getTotalTimeSpentInNanoSeconds());
        self::assertSame(1, $analytics->getTimeSpentRoutingInNanoSeconds());
        self::assertSame(1, $analytics->getTimeSpentProcessingMiddlewareInNanoseconds());
        self::assertSame(0, $analytics->getTimeSpentProcessingControllerInNanoseconds());

        self::assertSame(HttpStatus::INTERNAL_SERVER_ERROR, $analytics->getResponseStatusCode());
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

        self::assertSame($this->request, $analytics->getRequest());
        self::assertSame($this->controller->toString(), $analytics->getControllerName());
        self::assertSame(RoutingResolutionReason::RequestMatched, $analytics->getRoutingResolutionReason());
        self::assertSame($exception, $analytics->getThrownException());

        self::assertSame(6, $analytics->getTotalTimeSpentInNanoSeconds());
        self::assertSame(1, $analytics->getTimeSpentRoutingInNanoSeconds());
        self::assertSame(1, $analytics->getTimeSpentProcessingMiddlewareInNanoseconds());
        self::assertSame(1, $analytics->getTimeSpentProcessingControllerInNanoseconds());

        self::assertSame(HttpStatus::INTERNAL_SERVER_ERROR, $analytics->getResponseStatusCode());
    }


}
