<?php declare(strict_types=1);

namespace Labrador\Test\Unit\Web\Application\Analytics;

use Amp\Http\HttpStatus;
use Amp\Http\Server\Driver\Client;
use Amp\Http\Server\Request;
use Labrador\Web\Application\Analytics\LoggingRequestAnalyticsQueue;
use Labrador\Web\Application\Analytics\RequestAnalytics;
use Labrador\Web\HttpMethod;
use Labrador\Web\Router\RoutingResolutionReason;
use League\Uri\Http;
use Monolog\Handler\TestHandler;
use Monolog\Logger;
use Monolog\Processor\PsrLogMessageProcessor;
use PHPUnit\Framework\TestCase;
use RuntimeException;

final class LoggingRequestAnalyticsQueueTest extends TestCase {

    private LoggingRequestAnalyticsQueue $subject;

    private TestHandler $handler;

    protected function setUp() : void {
        $this->handler = new TestHandler();
        $this->subject = new LoggingRequestAnalyticsQueue(
            new Logger('logging-analytics-test', [$this->handler], [new PsrLogMessageProcessor()])
        );
    }

    public function testLoggingSuccessfulResponseHasCorrectOutput() : void {
        $analytics = new class($this->getMockBuilder(Client::class)->getMock()) implements RequestAnalytics {

            public function __construct(
                private readonly Client $client,
            ) {
            }

            public function request() : Request {
                return new Request($this->client, HttpMethod::Get->value, Http::createFromString('https://example.com/success'));
            }

            public function routingResolutionReason() : ?RoutingResolutionReason {
                return RoutingResolutionReason::RequestMatched;
            }

            public function controllerName() : ?string {
                return 'RoutedController';
            }

            public function thrownException() : ?\Throwable {
                return null;
            }

            public function totalTimeSpentInNanoSeconds() : int|float {
                return 10;
            }

            public function timeSpentRoutingInNanoSeconds() : int|float {
                return 1;
            }

            public function timeSpentProcessingMiddlewareInNanoseconds() : int|float {
                return 2;
            }

            public function timeSpentProcessingControllerInNanoseconds() : int|float {
                return 3;
            }

            public function responseStatusCode() : int {
                return HttpStatus::OK;
            }
        };
        $this->subject->queue($analytics);

        self::assertTrue($this->handler->hasInfo([
            'message' => 'Processed "GET /success" in 10 nanoseconds.',
            'context' => [
                'request' => 'GET /success',
                'resolution_reason' => RoutingResolutionReason::RequestMatched,
                'controller' => 'RoutedController',
                'total_time_spent' => 10,
                'time_spent_routing' => 1,
                'time_spent_middleware' => 2,
                'time_spent_controller' => 3,
                'response_code' => HttpStatus::OK
            ]
        ]));
    }

    public function testLoggingExceptionThrownResponseHasCorrectOutput() : void {
        $analytics = new class($this->getMockBuilder(Client::class)->getMock()) implements RequestAnalytics {

            public function __construct(
                private readonly Client $client,
            ) {
            }

            public function request() : Request {
                return new Request($this->client, HttpMethod::Get->value, Http::createFromString('https://example.com/failure'));
            }

            public function routingResolutionReason() : ?RoutingResolutionReason {
                return RoutingResolutionReason::RequestMatched;
            }

            public function controllerName() : ?string {
                return 'RoutedController';
            }

            public function thrownException() : ?\Throwable {
                return new RuntimeException('Known message');
            }

            public function totalTimeSpentInNanoSeconds() : int|float {
                return 10;
            }

            public function timeSpentRoutingInNanoSeconds() : int|float {
                return 1;
            }

            public function timeSpentProcessingMiddlewareInNanoseconds() : int|float {
                return 2;
            }

            public function timeSpentProcessingControllerInNanoseconds() : int|float {
                return 3;
            }

            public function responseStatusCode() : int {
                return HttpStatus::INTERNAL_SERVER_ERROR;
            }
        };
        $this->subject->queue($analytics);

        self::assertTrue($this->handler->hasInfo([
            'message' => 'Failed processing "GET /failure" in 10 nanoseconds.',
            'context' => [
                'request' => 'GET /failure',
                'resolution_reason' => RoutingResolutionReason::RequestMatched,
                'controller' => 'RoutedController',
                'total_time_spent' => 10,
                'time_spent_routing' => 1,
                'time_spent_middleware' => 2,
                'time_spent_controller' => 3,
                'response_code' => HttpStatus::INTERNAL_SERVER_ERROR,
                'exception_message' => 'Known message',
                'exception_class' => RuntimeException::class,
                'exception_file' => __FILE__,
                'exception_line' => 114
            ]
        ]));
    }
}
