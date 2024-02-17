<?php declare(strict_types=1);

namespace Labrador\Test\Unit\Web\Logging;

use Labrador\Logging\LoggerType;
use Labrador\Logging\MonologLoggerFactory;
use Labrador\Logging\MonologLoggerInitializer;
use Labrador\Logging\StdoutMonologLoggerInitializer;
use Monolog\Logger;
use Monolog\Processor\PsrLogMessageProcessor;
use Monolog\Test\TestCase;

class LoggerFactoryTest extends TestCase {

    private MonologLoggerInitializer $nullInitializer;
    private MonologLoggerInitializer $mockInitializer;

    protected function setUp() : void {
        $this->nullInitializer = new class implements MonologLoggerInitializer {

            public function initialize(Logger $logger, LoggerType $loggerType) : void {
                // TODO: Implement initialize() method.
            }
        };
        $this->mockInitializer = $this->getMockBuilder(MonologLoggerInitializer::class)->getMock();
    }

    public static function loggerTypeProvider() : array {
        return [
            LoggerType::Application->name => [LoggerType::Application],
            LoggerType::Router->name => [LoggerType::Router],
            LoggerType::WebServer->name => [LoggerType::WebServer],
            LoggerType::Worker->name => [LoggerType::Worker],
        ];
    }

    /**
     * @dataProvider loggerTypeProvider
     */
    public function testLoggerReturnedWithCorrectName(LoggerType $loggerType) : void {
        $subject = new MonologLoggerFactory($this->nullInitializer);
        $logger = $subject->createLogger($loggerType);

        self::assertInstanceOf(Logger::class, $logger);
        self::assertSame($loggerType->value, $logger->getName());
    }

    /**
     * @dataProvider loggerTypeProvider
     */
    public function testLoggerPassedToInitializer(LoggerType $loggerType) : void {
        $subject = new MonologLoggerFactory($this->mockInitializer);
        $this->mockInitializer->expects($this->once())
            ->method('initialize')
            ->with(
                $this->callback(static function (Logger $logger) use ($loggerType) : bool {
                    return $logger->getName() === $loggerType->value;
                }),
                $loggerType
            );

        $logger = $subject->createLogger($loggerType);

        self::assertInstanceOf(Logger::class, $logger);
    }

    /**
     * @dataProvider loggerTypeProvider
     */
    public function testCreatingLoggerOfSameTypeReturnsSameInstance(LoggerType $loggerType) : void {
        $subject = new MonologLoggerFactory($this->nullInitializer);

        $a = $subject->createLogger($loggerType);
        $b = $subject->createLogger($loggerType);

        self::assertSame($a, $b);
    }


}
