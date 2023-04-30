<?php declare(strict_types=1);

namespace Labrador\Http\Test\Unit\Logging;

use Amp\Http\Server\ErrorHandler;
use Amp\Http\Server\HttpServer;
use Amp\Http\Server\HttpServerStatus;
use Amp\Http\Server\RequestHandler;
use Labrador\Http\Server\AccessLoggingHttpServer;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;

final class AccessLoggingHttpServerTest extends TestCase {

    private HttpServer&MockObject $server;

    private AccessLoggingHttpServer $subject;

    protected function setUp() : void {
        $this->server = $this->getMockBuilder(HttpServer::class)->getMock();
        $this->subject = new AccessLoggingHttpServer(
            $this->server,
            new NullLogger()
        );
    }

    public function testStartDelegatedToProvidedHttpServer() : void {
        $this->server->expects($this->once())
            ->method('start')
            ->with(
                $this->isInstanceOf(RequestHandler::class),
                $this->isInstanceOf(ErrorHandler::class)
            );

        $requestHandler = $this->getMockBuilder(RequestHandler::class)->getMock();
        $errorHandler = $this->getMockBuilder(ErrorHandler::class)->getMock();

        $this->subject->start($requestHandler, $errorHandler);
    }

    public function testStopDelegatedToProvidedHttpServer() : void {
        $this->server->expects($this->once())->method('stop');

        $this->subject->stop();
    }

    public function testOnStartDelegatedToProvidedHttpServer() : void {
        $closure = function() {};

        $this->server->expects($this->once())
            ->method('onStart')
            ->with($closure);

        $this->subject->onStart($closure);
    }

    public function testOnStopDelegatedToProvidedHttpServer() : void {
        $closure = function() {};

        $this->server->expects($this->once())
            ->method('onStop')
            ->with($closure);

        $this->subject->onStop($closure);
    }

    public function testGetStatusDelegatedToProvidedHttpServer() : void {
        $status = HttpServerStatus::Started;

        $this->server->expects($this->once())
            ->method('getStatus')
            ->willReturn($status);

        $actual = $this->subject->getStatus();

        self::assertSame($status, $actual);
    }


}