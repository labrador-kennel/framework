<?php

namespace Cspray\Labrador\Http\Test\Integration;

use Amp\Http\Client\HttpClientBuilder;
use Amp\Http\Client\Request;
use Amp\Http\Status;
use Amp\PHPUnit\AsyncTestCase;
use Cspray\AnnotatedContainer\AnnotatedContainer;
use Cspray\AnnotatedContainer\Bootstrap\Bootstrap as AnnotatedContainerBootstrap;
use Cspray\Labrador\Http\Application;
use Cspray\Labrador\Http\Bootstrap;
use Cspray\Labrador\Http\Test\Helper\StreamBuffer;
use Cspray\Labrador\Http\Test\Helper\VfsDirectoryResolver;
use Cspray\Labrador\HttpDummyApp\Middleware\BarMiddleware;
use Cspray\Labrador\HttpDummyApp\Middleware\BazMiddleware;
use Cspray\Labrador\HttpDummyApp\Middleware\FooMiddleware;
use Cspray\Labrador\HttpDummyApp\Middleware\QuxMiddleware;
use Cspray\Labrador\HttpDummyApp\CountingService;
use Cspray\Labrador\HttpDummyApp\MiddlewareCallRegistry;
use org\bovigo\vfs\vfsStream as VirtualFilesystem;
use org\bovigo\vfs\vfsStreamDirectory as VirtualDirectory;
use org\bovigo\vfs\vfsStreamWrapper as VirtualStream;
use Ramsey\Uuid\Uuid;

class HttpServerTest extends AsyncTestCase {

    private static AnnotatedContainer $container;
    private static Application $app;
    private static VirtualDirectory $vfs;
    private static $streamFilter;

    public static function setUpBeforeClass() : void {
        if (!in_array('test.stream.buffer', stream_get_filters())) {
            self::assertTrue(stream_filter_register('test.stream.buffer', StreamBuffer::class));
        }
        self::$streamFilter = stream_filter_append(STDOUT, 'test.stream.buffer');
        self::assertIsResource(self::$streamFilter);
        self::assertEmpty(StreamBuffer::getBuffer());

        self::$vfs = VirtualFilesystem::setup();
        self::writeStandardConfigurationFile();

        $bootstrap = new AnnotatedContainerBootstrap(directoryResolver: new VfsDirectoryResolver());
        $results = (new Bootstrap($bootstrap, profiles: ['default', 'integration-test']))->bootstrapApplication();

        self::$container = $results->container;
        self::$app = $results->application;

        self::$app->start();
    }

    private static function writeStandardConfigurationFile() : void {
        $config = <<<XML
<?xml version="1.0" encoding="UTF-8" ?>
<annotatedContainer xmlns="https://annotated-container.cspray.io/schema/annotated-container.xsd">
    <scanDirectories>
        <source>
            <dir>src</dir>
            <dir>dummy_app</dir>
            <dir>vendor/cspray/labrador-async-event/src</dir>
        </source>
    </scanDirectories>
    <containerDefinitionBuilderContextConsumer>
        Cspray\Labrador\Http\DependencyInjection\ThirdPartyServicesProvider
    </containerDefinitionBuilderContextConsumer>
    <observers>
        <fqcn>Cspray\Labrador\Http\DependencyInjection\AutowireObserver</fqcn>
    </observers>
</annotatedContainer>
XML;

        VirtualFilesystem::newFile('annotated-container.xml')
            ->withContent($config)
            ->at(self::$vfs);
    }

    public static function tearDownAfterClass() : void {
        self::$app->stop();
        VirtualStream::unregister();
        StreamBuffer::clearBuffer();
        self::assertTrue(stream_filter_remove(self::$streamFilter));
    }

    protected function setUp() : void {
        parent::setUp();
        StreamBuffer::clearBuffer();
        self::$container->get(MiddlewareCallRegistry::class)->reset();
    }

    public function testMakingHelloWorldCall() : void {
        $client = (new HttpClientBuilder())->build();

        $response = $client->request(new Request('http://localhost:4200/hello/world'));

        self::assertSame(Status::OK, $response->getStatus());
        self::assertSame('Hello, world!', $response->getBody()->buffer());
    }

    public function testMiddlewareCalledInPriorityOrder() : void {
        $client = (new HttpClientBuilder())->build();

        $response = $client->request(new Request('http://localhost:4200/hello/world'));

        self::assertSame(Status::OK, $response->getStatus());

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

        self::assertSame(Status::OK, $response->getStatus());

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

        self::assertSame(Status::OK, $response->getStatus());
        self::assertSame('Received method POST', $response->getBody()->buffer());
    }

    public function testDtoControllerPutMethodsAddedAsController() : void {
        $client = (new HttpClientBuilder())->build();

        $request = new Request('http://localhost:4200/dto/url', 'PUT');
        $response = $client->request($request);

        self::assertSame(Status::OK, $response->getStatus());
        self::assertSame('Received UriInterface http://localhost:4200/dto/url', $response->getBody()->buffer());
    }

    public function testDtoControllerMultipleParameters() : void {
        $client = (new HttpClientBuilder())->build();

        $request = new Request('http://localhost:4200/dto/method-and-url');
        $response = $client->request($request);

        self::assertSame(Status::OK, $response->getStatus());
        self::assertSame('Received GET and http://localhost:4200/dto/method-and-url', $response->getBody()->buffer());
    }

    public function testDtoControllerRouteParams() : void {
        $client = (new HttpClientBuilder())->build();

        $id = Uuid::uuid6();

        $request = new Request('http://localhost:4200/dto/widget/' . $id->toString(), 'POST');
        $response = $client->request($request);

        self::assertSame(Status::OK, $response->getStatus());
        self::assertSame('Received widget id as UuidInterface ' . $id->toString(), $response->getBody()->buffer());
    }

    public function testDtoControllerDelete() : void {
        $client = (new HttpClientBuilder())->build();

        $id = Uuid::uuid6();

        $request = new Request('http://localhost:4200/dto/widget/' . $id->toString(), 'DELETE');
        $response = $client->request($request);

        self::assertSame(Status::OK, $response->getStatus());
        self::assertSame('Received request to delete widget with id ' . $id->toString(), $response->getBody()->buffer());
    }

    public function testCountingServiceCalled() : void {
        $client = (new HttpClientBuilder())->build();

        $request = new Request('http://localhost:4200/dto/counting-service', 'GET');
        $response = $client->request($request);

        self::assertSame(Status::OK, $response->getStatus());
        self::assertSame('Received method and called service GET', $response->getBody()->buffer());
        self::assertSame(1, self::$container->get(CountingService::class)->getIt());
    }

    public function testRouteMiddlewareRespected() : void {
        $client = (new HttpClientBuilder())->build();

        $request = new Request('http://localhost:4200/hello/middleware', 'GET');
        $response = $client->request($request);

        self::assertSame(Status::OK, $response->getStatus());
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

        self::assertSame(Status::OK, $response->getStatus());
        self::assertSame($method . ' - Universe', $response->getBody()->buffer());
    }

}