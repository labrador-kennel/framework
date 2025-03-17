<?php

namespace Labrador\Test\Integration;

use Amp\Http\Client\HttpClientBuilder;
use Amp\Http\Client\Request;
use Amp\Http\HttpStatus;
use Amp\PHPUnit\AsyncTestCase;
use Cspray\AnnotatedContainer\AnnotatedContainer;
use Cspray\StreamBufferIntercept\BufferIdentifier;
use Cspray\StreamBufferIntercept\StreamBuffer;
use Labrador\DummyApp\DummyLoggerFactory;
use Labrador\DummyApp\Middleware\BarMiddleware;
use Labrador\DummyApp\Middleware\BazMiddleware;
use Labrador\DummyApp\Middleware\FooMiddleware;
use Labrador\DummyApp\Middleware\QuxMiddleware;
use Labrador\DummyApp\MiddlewareCallRegistry;
use Labrador\Test\BootstrapAwareTestTrait;
use Labrador\Test\Helper\VfsDirectoryResolver;
use Labrador\Web\Application\Application;
use Monolog\Handler\TestHandler;
use Monolog\LogRecord;
use org\bovigo\vfs\vfsStream as VirtualFilesystem;
use org\bovigo\vfs\vfsStreamDirectory as VirtualDirectory;
use org\bovigo\vfs\vfsStreamWrapper as VirtualStream;
use PHPUnit\Framework\Constraint\StringMatchesFormatDescription;

class HttpServerTest extends AsyncTestCase {

    use BootstrapAwareTestTrait;

    private static AnnotatedContainer $container;
    private static Application $app;
    private static VirtualDirectory $vfs;

    private static BufferIdentifier $stdout;
    private static BufferIdentifier $stderr;

    public static function setUpBeforeClass() : void {
        StreamBuffer::register();
        self::$stdout = StreamBuffer::intercept(STDOUT);
        self::$stderr = StreamBuffer::intercept(STDERR);
        self::$vfs = VirtualFilesystem::setup();
        self::writeStandardConfigurationFile();

        self::$container = self::getContainer(['default', 'integration-test'], new VfsDirectoryResolver());
        self::$app = self::$container->get(Application::class);

        self::$app->start();
    }

    private static function writeStandardConfigurationFile() : void {
        VirtualFilesystem::newFile('annotated-container.xml')
            ->withContent(self::getDefaultConfiguration())
            ->at(self::$vfs);
    }

    public static function tearDownAfterClass() : void {
        self::$app->stop();
        StreamBuffer::stopIntercepting(self::$stdout);
        StreamBuffer::stopIntercepting(self::$stderr);
        VirtualStream::unregister();
    }

    protected function setUp() : void {
        parent::setUp();
        self::$container->get(MiddlewareCallRegistry::class)->reset();
        StreamBuffer::reset(self::$stdout);
        StreamBuffer::reset(self::$stderr);
    }

    public function testMakingHelloWorldCall() : void {
        $client = (new HttpClientBuilder())->build();

        $response = $client->request(new Request('http://localhost:4200/hello/world'));

        self::assertSame(HttpStatus::OK, $response->getStatus());
        self::assertSame('Hello, world!', $response->getBody()->buffer());
    }

    public function testGlobalMiddlewareAddedFromAppRouteListenerIsInvoked() : void {
        $client = (new HttpClientBuilder())->build();

        $response = $client->request(new Request('http://localhost:4200/hello/world'));

        self::assertSame(HttpStatus::OK, $response->getStatus());

        /** @var MiddlewareCallRegistry $callRegistry */
        $callRegistry = self::$container->get(MiddlewareCallRegistry::class);

        $expected = [
            QuxMiddleware::class,
            FooMiddleware::class,
            BazMiddleware::class,
            BarMiddleware::class
        ];

        self::assertSame($expected, $callRegistry->getCalled());
    }

    public function testRouteMiddlewareRespected() : void {
        $client = (new HttpClientBuilder())->build();

        $request = new Request('http://localhost:4200/hello/middleware', 'GET');
        $response = $client->request($request);

        self::assertSame(HttpStatus::OK, $response->getStatus());
        self::assertSame('Hello, Universe!', $response->getBody()->buffer());
    }

    public function methodDataProvider() : array {
        return [
            ['GET'],
            ['POST'],
            ['PUT'],
            ['DELETE']
        ];
    }

    public function testCorrectAccessLogOutputSendToStdout() : void {
        $client = (new HttpClientBuilder())->build();

        $request = new Request('http://localhost:4200/hello/world');
        $client->request($request);

        $handler = self::$container->get(DummyLoggerFactory::class)->testHandler;
        self::assertInstanceOf(TestHandler::class, $handler);

        self::assertTrue($handler->hasInfoThatPasses(static function (LogRecord $record) {
            $expected = <<<TEXT
%a labrador.app.INFO: "GET http://localhost:%d/hello/world" 200 "OK" HTTP/1.1 127.0.0.1:%d on 127.0.0.1:%d {"request":{"method":"GET","uri":"http://localhost:4200/hello/world","protocolVersion":"1.1","local":"127.0.0.1:%d","remote":"127.0.0.1:%d"},"response":{"status":200,"reason":"OK"}} []
TEXT;
            return (new StringMatchesFormatDescription($expected))->evaluate(
                other: $record->formatted,
                returnResult: true
            );
        }));
    }

    public function testExceptionThrowHasCorrectLogOutputSentToStdout() : void {
        $client = (new HttpClientBuilder())->build();

        $request = new Request('http://localhost:4200/exception');
        $client->request($request);

        $handler = self::$container->get(DummyLoggerFactory::class)->testHandler;
        self::assertInstanceOf(TestHandler::class, $handler);

        self::assertTrue($handler->hasErrorThatPasses(static function (LogRecord $record) {
            $expectedContext = '{"client_address":"127.0.0.1:%d","method":"GET","path":"/exception","exception_class":"RuntimeException","file":"%a/RouterListener.php","line_number":47,"exception_message":"A message detailing what went wrong that should show up in logs.","stack_trace":%a}';
            $expected = <<<TEXT
%a labrador.app.ERROR: RuntimeException thrown in %a/RouterListener.php#L47 handling client 127.0.0.1:%d with request "GET /exception". Message: A message detailing what went wrong that should show up in logs. $expectedContext []
TEXT;
            return (new StringMatchesFormatDescription($expected))->evaluate(
                other: $record->formatted,
                returnResult: true
            );
        }));
    }
}
