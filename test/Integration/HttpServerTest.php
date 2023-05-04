<?php

namespace Labrador\Http\Test\Integration;

use Amp\Http\Client\HttpClientBuilder;
use Amp\Http\Client\Request;
use Amp\Http\Cookie\ResponseCookie;
use Amp\Http\HttpStatus;
use Amp\PHPUnit\AsyncTestCase;
use Cspray\AnnotatedContainer\AnnotatedContainer;
use Cspray\StreamBufferIntercept\BufferIdentifier;
use Cspray\StreamBufferIntercept\StreamBuffer;
use Labrador\Http\Application\Application;
use Labrador\Http\Test\BootstrapAwareTestTrait;
use Labrador\Http\Test\Helper\VfsDirectoryResolver;
use Labrador\HttpDummyApp\Controller\SessionDtoController;
use Labrador\HttpDummyApp\CountingService;
use Labrador\HttpDummyApp\Middleware\BarMiddleware;
use Labrador\HttpDummyApp\Middleware\BazMiddleware;
use Labrador\HttpDummyApp\Middleware\FooMiddleware;
use Labrador\HttpDummyApp\Middleware\QuxMiddleware;
use Labrador\HttpDummyApp\MiddlewareCallRegistry;
use org\bovigo\vfs\vfsStream as VirtualFilesystem;
use org\bovigo\vfs\vfsStreamDirectory as VirtualDirectory;
use org\bovigo\vfs\vfsStreamWrapper as VirtualStream;
use Ramsey\Uuid\Uuid;

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

    public function testMiddlewareCalledInPriorityOrder() : void {
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

    public function testDtoControllerGetMethodsAddedAsController() : void {
        $client = (new HttpClientBuilder())->build();

        $request = new Request('http://localhost:4200/dto/headers');
        $request->setHeader('Custom-Header', 'my-header-val');
        $response = $client->request($request);

        self::assertSame(HttpStatus::OK, $response->getStatus());

        $expectedHeaders = [
            'custom-header' => ['my-header-val'],
            'accept' => ['*/*'],
            'user-agent' => ['amphp/http-client @ v5.x'],
            'accept-encoding' => ['gzip, deflate, identity'],
            'host' => ['localhost:4200']
        ];
        self::assertSame('Received headers ' . json_encode($expectedHeaders), $response->getBody()->buffer());
    }

    public function testDtoControllerPostMethodsAddedAsController() : void {
        $client = (new HttpClientBuilder())->build();

        $request = new Request('http://localhost:4200/dto/method', 'POST');
        $response = $client->request($request);

        self::assertSame(HttpStatus::OK, $response->getStatus());
        self::assertSame('Received method POST', $response->getBody()->buffer());
    }

    public function testDtoControllerPutMethodsAddedAsController() : void {
        $client = (new HttpClientBuilder())->build();

        $request = new Request('http://localhost:4200/dto/url', 'PUT');
        $response = $client->request($request);

        self::assertSame(HttpStatus::OK, $response->getStatus());
        self::assertSame('Received UriInterface http://localhost:4200/dto/url', $response->getBody()->buffer());
    }

    public function testDtoControllerMultipleParameters() : void {
        $client = (new HttpClientBuilder())->build();

        $request = new Request('http://localhost:4200/dto/method-and-url');
        $response = $client->request($request);

        self::assertSame(HttpStatus::OK, $response->getStatus());
        self::assertSame('Received GET and http://localhost:4200/dto/method-and-url', $response->getBody()->buffer());
    }

    public function testDtoControllerRouteParams() : void {
        $client = (new HttpClientBuilder())->build();

        $id = Uuid::uuid6();

        $request = new Request('http://localhost:4200/dto/widget/' . $id->toString(), 'POST');
        $response = $client->request($request);

        self::assertSame(HttpStatus::OK, $response->getStatus());
        self::assertSame('Received widget id as UuidInterface ' . $id->toString(), $response->getBody()->buffer());
    }

    public function testDtoControllerDelete() : void {
        $client = (new HttpClientBuilder())->build();

        $id = Uuid::uuid6();

        $request = new Request('http://localhost:4200/dto/widget/' . $id->toString(), 'DELETE');
        $response = $client->request($request);

        self::assertSame(HttpStatus::OK, $response->getStatus());
        self::assertSame('Received request to delete widget with id ' . $id->toString(), $response->getBody()->buffer());
    }

    public function testCountingServiceCalled() : void {
        $client = (new HttpClientBuilder())->build();

        $request = new Request('http://localhost:4200/dto/counting-service', 'GET');
        $response = $client->request($request);

        self::assertSame(HttpStatus::OK, $response->getStatus());
        self::assertSame('Received method and called service GET', $response->getBody()->buffer());
        self::assertSame(1, self::$container->get(CountingService::class)->getIt());
    }

    public function testRouteMiddlewareRespected() : void {
        $client = (new HttpClientBuilder())->build();

        $request = new Request('http://localhost:4200/hello/middleware', 'GET');
        $response = $client->request($request);

        $output = StreamBuffer::output(self::$stdout);

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

    /**
     * @dataProvider methodDataProvider
     */
    public function testDtoRouteMiddlewareRespected(string $method) : void {
        $client = (new HttpClientBuilder())->build();

        $request = new Request('http://localhost:4200/dto/middleware', $method);
        $response = $client->request($request);

        self::assertSame(HttpStatus::OK, $response->getStatus());
        self::assertSame($method . ' - Universe', $response->getBody()->buffer());
    }

    public function testDtoActionsHaveSessionAccessWhenDefinedOnClass() : void {
        $client = (new HttpClientBuilder())->build();

        $request = new Request('http://localhost:4200/dto/controller-session/write');
        $response = $client->request($request);

        self::assertSame(200, $response->getStatus());

        $cookie = ResponseCookie::fromHeader($response->getHeader('Set-Cookie'));

        self::assertSame('session', $cookie->getName());
        self::assertNotEmpty($cookie->getValue());

        $request = new Request('http://localhost:4200/dto/controller-session/read');
        $request->setHeader('Cookie', 'session=' . $cookie->getValue());

        $response = $client->request($request);

        self::assertSame(SessionDtoController::class . '::write', $response->getBody()->read());
    }

    public function testDtoActionsHaveSessionAccessWhenDefinedOnMethods() : void {
        $client = (new HttpClientBuilder())->build();

        $request = new Request('http://localhost:4200/dto/action-session/write');
        $response = $client->request($request);

        self::assertSame(200, $response->getStatus());

        $cookie = ResponseCookie::fromHeader($response->getHeader('Set-Cookie'));

        self::assertSame('session', $cookie->getName());
        self::assertNotEmpty($cookie->getValue());

        $request = new Request('http://localhost:4200/dto/action-session/read');
        $request->setHeader('Cookie', 'session=' . $cookie->getValue());

        $response = $client->request($request);

        self::assertSame('Known Session Value', $response->getBody()->read());
    }

    public function testCorrectAccessLogOutputSendToStdout() : void {
        $client = (new HttpClientBuilder())->build();

        $request = new Request('http://localhost:4200/hello/world');
        $client->request($request);

        $expected = <<<TEXT
%a
%a labrador.web-server.INFO: "GET http://localhost:%d/hello/world" 200 "OK" HTTP/1.1 127.0.0.1:%d on 127.0.0.1:%d {"request":{"method":"GET","uri":"http://localhost:4200/hello/world","protocolVersion":"1.1","local":"127.0.0.1:%d","remote":"127.0.0.1:%d"},"response":{"status":200,"reason":"OK"}} []
%a
TEXT;

        self::assertStringMatchesFormat(
            $expected,
            StreamBuffer::output(self::$stdout)
        );
    }

    public function testExceptionThrowHasCorrectLogOutputSentToStdout() : void {
        $client = (new HttpClientBuilder())->build();

        $request = new Request('http://localhost:4200/exception');
        $client->request($request);

        $expectedContext = '{"client_address":"127.0.0.1:%d","method":"GET","path":"/exception","exception_class":"RuntimeException","file":"%a/RouterListener.php","line_number":28,"exception_message":"A message detailing what went wrong that should show up in logs.","stack_trace":%a}';
        $expected = <<<TEXT
%a
%a labrador.app.ERROR: RuntimeException thrown in %a/RouterListener.php#L28 handling client 127.0.0.1:%d with request "GET /exception". Message: A message detailing what went wrong that should show up in logs. $expectedContext []
TEXT;

        self::assertStringMatchesFormat(
            $expected,
            StreamBuffer::output(self::$stdout)
        );
    }
}