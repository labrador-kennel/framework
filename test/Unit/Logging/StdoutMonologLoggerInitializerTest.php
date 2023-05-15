<?php declare(strict_types=1);

namespace Labrador\Test\Unit\Logging;

use Amp\ByteStream\StreamException;
use Amp\Log\ConsoleFormatter;
use Amp\Log\StreamHandler;
use Cspray\StreamBufferIntercept\BufferIdentifier;
use Cspray\StreamBufferIntercept\StreamBuffer;
use Labrador\Logging\LoggerType;
use Labrador\Logging\StdoutMonologLoggerInitializer;
use Monolog\Logger;
use PHPUnit\Framework\TestCase;

final class StdoutMonologLoggerInitializerTest extends TestCase {

    private StdoutMonologLoggerInitializer $subject;

    private Logger $logger;

    private BufferIdentifier $bufferIdentifier;

    protected function setUp() : void {
        StreamBuffer::register();

        $this->logger = new Logger('stdout-initializer-test');
        $this->subject = new StdoutMonologLoggerInitializer();
        $this->bufferIdentifier = StreamBuffer::intercept(STDOUT);
        StreamBuffer::reset($this->bufferIdentifier);
    }

    protected function tearDown() : void {
        StreamBuffer::stopIntercepting($this->bufferIdentifier);
    }

    public function testInitializedLoggerHasCorrectHandler() : void {
        self::assertCount(0, $this->logger->getHandlers());

        $this->subject->initialize($this->logger, LoggerType::Application);

        self::assertCount(1, $this->logger->getHandlers());

        $handler = $this->logger->getHandlers()[0];
        self::assertInstanceOf(StreamHandler::class, $handler);
        self::assertInstanceOf(ConsoleFormatter::class, $handler->getFormatter());
    }

    public function testLoggerSendsMessagesToStdout() : void {
        $this->subject->initialize($this->logger, LoggerType::Worker);

        $this->logger->info('Message sent to STDOUT');

        self::assertStringContainsString(
            'Message sent to STDOUT',
            StreamBuffer::output($this->bufferIdentifier)
        );
    }



}