<?php

namespace Cspray\Labrador\Http\Test\Unit\Controller;

use Amp\Http\Server\Driver\Client;
use Amp\Http\Server\Request;
use Cspray\AnnotatedContainer\AnnotatedContainer;
use Cspray\Labrador\Http\Controller\DtoControllerHandler;
use Cspray\Labrador\Http\ErrorHandlerFactory;
use Cspray\Labrador\Http\HttpMethod;
use Cspray\Labrador\Http\Test\BootstrapAwareTestTrait;
use Cspray\Labrador\Http\Test\Helper\StreamBuffer;
use Cspray\Labrador\HttpDummyApp\Controller\CheckDtoController;
use League\Uri\Http;
use PHPUnit\Framework\TestCase;
use org\bovigo\vfs\vfsStream as VirtualFilesystem;
use org\bovigo\vfs\vfsStreamDirectory as VirtualDirectory;
use Ramsey\Uuid\Uuid;

final class DtoControllerHandlerTest extends TestCase {

    use BootstrapAwareTestTrait;

    private AnnotatedContainer $container;
    private VirtualDirectory $vfs;
    private $streamFilter;

    protected function setUp() : void {
        if (!in_array('test.stream.buffer', stream_get_filters())) {
            self::assertTrue(stream_filter_register('test.stream.buffer', StreamBuffer::class));
        }
        $this->streamFilter = stream_filter_append(STDOUT, 'test.stream.buffer');
        self::assertIsResource($this->streamFilter);
        self::assertEmpty(StreamBuffer::getBuffer());
        $this->vfs = VirtualFilesystem::setup();
        VirtualFilesystem::newFile('annotated-container.xml')
            ->withContent(self::getDefaultConfiguration())
            ->at($this->vfs);
        $this->container = self::getContainer(['default', 'unit-test']);
    }

    protected function tearDown() : void {
        parent::tearDown();
        StreamBuffer::clearBuffer();
        self::assertTrue(stream_filter_remove($this->streamFilter));
    }

    public function testInvokeObjectHeadersDto() : void {
        $controller = new CheckDtoController();
        $subject = new DtoControllerHandler($controller->checkHeaders(...), $this->container, new ErrorHandlerFactory());

        $request = new Request(
            $this->getMockBuilder(Client::class)->getMock(),
            HttpMethod::Get->value,
            Http::createFromString('http://example.com'),
            ['Custom-Header' => 'whatever']
        );

        $response = $subject->handleRequest($request);

        self::assertSame(200, $response->getStatus());
        self::assertSame('Received headers {"custom-header":["whatever"]}', $response->getBody()->read());
    }

    public function testInvokeObjectMethodDto() : void {
        $controller = new CheckDtoController();
        $subject = new DtoControllerHandler($controller->checkMethod(...), $this->container, new ErrorHandlerFactory());

        $request = new Request(
            $this->getMockBuilder(Client::class)->getMock(),
            HttpMethod::Post->value,
            Http::createFromString('http://example.com')
        );

        $response = $subject->handleRequest($request);

        self::assertSame(200, $response->getStatus());
        self::assertSame('Received method POST', $response->getBody()->read());
    }

    public function testInvokeObjectHeadersParamNameDto() : void {
        $controller = new CheckDtoController();
        $subject = new DtoControllerHandler($controller->checkHeadersParamName(...), $this->container, new ErrorHandlerFactory());

        $request = new Request(
            $this->getMockBuilder(Client::class)->getMock(),
            HttpMethod::Get->value,
            Http::createFromString('http://example.com'),
            ['Custom-Header' => 'whatever']
        );

        $response = $subject->handleRequest($request);

        self::assertSame(200, $response->getStatus());
        self::assertSame('Received headers {"custom-header":["whatever"]}', $response->getBody()->read());
    }

    public function testInvokeObjectSingleHeaderArrayDto() : void {
        $controller = new CheckDtoController();
        $subject = new DtoControllerHandler($controller->checkSingleHeaderArray(...), $this->container, new ErrorHandlerFactory());

        $request = new Request(
            $this->getMockBuilder(Client::class)->getMock(),
            HttpMethod::Get->value,
            Http::createFromString('http://example.com'),
            [
                'Content-Type' => 'application/json',
                'Custom-Header' => ['foo', 'bar', 'baz']
            ]
        );

        $response = $subject->handleRequest($request);

        self::assertSame(200, $response->getStatus());
        self::assertSame('Received header for Custom-Header ["foo","bar","baz"]', $response->getBody()->read());
    }

    public function testInvokeObjectSingleHeaderStringDto() : void {
        $controller = new CheckDtoController();
        $subject = new DtoControllerHandler($controller->checkSingleHeaderString(...), $this->container, new ErrorHandlerFactory());

        $request = new Request(
            $this->getMockBuilder(Client::class)->getMock(),
            HttpMethod::Get->value,
            Http::createFromString('http://example.com'),
            [
                'Content-Type' => 'application/json',
                'Custom-Header' => ['foo', 'bar', 'baz'],
                'Authorization' => 'some-token'
            ]
        );

        $response = $subject->handleRequest($request);

        self::assertSame(200, $response->getStatus());
        self::assertSame('Received Authorization header some-token', $response->getBody()->read());
    }

    public function testInvokeObjectUrlDto() : void {
        $controller = new CheckDtoController();
        $subject = new DtoControllerHandler($controller->checkUrl(...), $this->container, new ErrorHandlerFactory());

        $request = new Request(
            $this->getMockBuilder(Client::class)->getMock(),
            HttpMethod::Get->value,
            Http::createFromString('http://example.com'),
        );

        $response = $subject->handleRequest($request);

        self::assertSame(200, $response->getStatus());
        self::assertSame('Received UriInterface http://example.com', $response->getBody()->read());
    }

    public function testInvokeObjectQueryStringDto() : void {
        $controller = new CheckDtoController();
        $subject = new DtoControllerHandler($controller->getQueryAsString(...), $this->container, new ErrorHandlerFactory());

        $request = new Request(
            $this->getMockBuilder(Client::class)->getMock(),
            HttpMethod::Get->value,
            Http::createFromString('http://example.com?foo=bar&bar=baz'),
        );

        $response = $subject->handleRequest($request);

        self::assertSame(200, $response->getStatus());
        self::assertSame('Received query as string foo=bar&bar=baz', $response->getBody()->read());
    }

    public function testInvokeObjectQueryQueryInterfaceDto() : void {
        $controller = new CheckDtoController();
        $subject = new DtoControllerHandler($controller->checkQueryInterface(...), $this->container, new ErrorHandlerFactory());

        $request = new Request(
            $this->getMockBuilder(Client::class)->getMock(),
            HttpMethod::Get->value,
            Http::createFromString('http://example.com?foo=bar&bar=baz'),
        );

        $response = $subject->handleRequest($request);

        self::assertSame(200, $response->getStatus());
        self::assertSame('Received query as QueryInterface foo=bar&bar=baz', $response->getBody()->read());
    }

    public function testInvokeObjectQueryQueryDto() : void {
        $controller = new CheckDtoController();
        $subject = new DtoControllerHandler($controller->checkQueryComponent(...), $this->container, new ErrorHandlerFactory());

        $request = new Request(
            $this->getMockBuilder(Client::class)->getMock(),
            HttpMethod::Get->value,
            Http::createFromString('http://example.com?foo=bar&bar=baz'),
        );

        $response = $subject->handleRequest($request);

        self::assertSame(200, $response->getStatus());
        self::assertSame('Received query as Query foo=bar&bar=baz', $response->getBody()->read());
    }

    public function testInvokeObjectRouteParamDto() : void {
        $controller = new CheckDtoController();
        $subject = new DtoControllerHandler($controller->checkRouteParam(...), $this->container, new ErrorHandlerFactory());

        $request = new Request(
            $this->getMockBuilder(Client::class)->getMock(),
            HttpMethod::Get->value,
            Http::createFromString('http://example.com'),
        );
        $request->setAttribute('id', 'my-id-val');

        $response = $subject->handleRequest($request);

        self::assertSame(200, $response->getStatus());
        self::assertSame('Received widget id as string my-id-val', $response->getBody()->read());
    }

    public function testInvokeObjectRouteParamUuidDto() : void {
        $controller = new CheckDtoController();
        $subject = new DtoControllerHandler($controller->checkRouteParamUuid(...), $this->container, new ErrorHandlerFactory());

        $id = Uuid::uuid6();
        $request = new Request(
            $this->getMockBuilder(Client::class)->getMock(),
            HttpMethod::Post->value,
            Http::createFromString('http://example.com'),
        );
        $request->setAttribute('uuid', $id->toString());

        $response = $subject->handleRequest($request);

        self::assertSame(200, $response->getStatus());
        self::assertSame('Received widget id as UuidInterface ' . $id->toString(), $response->getBody()->read());
    }

    public function testInvokeObjectUrlDtoByType() : void {
        $controller = new CheckDtoController();
        $subject = new DtoControllerHandler($controller->checkUriInjectedByType(...), $this->container, new ErrorHandlerFactory());

        $request = new Request(
            $this->getMockBuilder(Client::class)->getMock(),
            HttpMethod::Get->value,
            Http::createFromString('http://example.com/some/path'),
        );

        $response = $subject->handleRequest($request);

        self::assertSame(200, $response->getStatus());
        self::assertSame('Received request URL as type http://example.com/some/path', $response->getBody()->read());
    }

    public function testInvokeObjectQueryInterfaceDtoByType() : void {
        $controller = new CheckDtoController();
        $subject = new DtoControllerHandler($controller->checkQueryInterfaceByType(...), $this->container, new ErrorHandlerFactory());

        $request = new Request(
            $this->getMockBuilder(Client::class)->getMock(),
            HttpMethod::Get->value,
            Http::createFromString('http://example.com/some/path?foo=bar&bar=baz'),
        );

        $response = $subject->handleRequest($request);

        self::assertSame(200, $response->getStatus());
        self::assertSame('Received QueryInterface as type foo=bar&bar=baz', $response->getBody()->read());
    }

    public function testInvokeObjectQueryDtoByType() : void {
        $controller = new CheckDtoController();
        $subject = new DtoControllerHandler($controller->checkQueryByType(...), $this->container, new ErrorHandlerFactory());

        $request = new Request(
            $this->getMockBuilder(Client::class)->getMock(),
            HttpMethod::Get->value,
            Http::createFromString('http://example.com/some/path?foo=bar&bar=baz'),
        );

        $response = $subject->handleRequest($request);

        self::assertSame(200, $response->getStatus());
        self::assertSame('Received Query as type foo=bar&bar=baz', $response->getBody()->read());
    }

    public function testInvokeObjectRequestBodyByType() : void {
        $controller = new CheckDtoController();
        $subject = new DtoControllerHandler($controller->checkBodyByType(...), $this->container, new ErrorHandlerFactory());

        $request = new Request(
            $this->getMockBuilder(Client::class)->getMock(),
            HttpMethod::Get->value,
            Http::createFromString('http://example.com/some/path?foo=bar&bar=baz'),
            body: 'The request body'
        );

        $response = $subject->handleRequest($request);

        self::assertSame(200, $response->getStatus());
        self::assertSame('Received body as type The request body', $response->getBody()->read());
    }

    public function testInvokeObjectRequestBodyAsString() : void {
        $controller = new CheckDtoController();
        $subject = new DtoControllerHandler($controller->checkBodyAsString(...), $this->container, new ErrorHandlerFactory());

        $request = new Request(
            $this->getMockBuilder(Client::class)->getMock(),
            HttpMethod::Get->value,
            Http::createFromString('http://example.com/some/path?foo=bar&bar=baz'),
            body: 'The request body'
        );

        $response = $subject->handleRequest($request);

        self::assertSame(200, $response->getStatus());
        self::assertSame('Received body as string The request body', $response->getBody()->read());
    }

    public function testInvokeObjectRequestBodyAsTypeAttribute() : void {
        $controller = new CheckDtoController();
        $subject = new DtoControllerHandler($controller->checkBodyAsTypeAttribute(...), $this->container, new ErrorHandlerFactory());

        $request = new Request(
            $this->getMockBuilder(Client::class)->getMock(),
            HttpMethod::Get->value,
            Http::createFromString('http://example.com/some/path?foo=bar&bar=baz'),
            body: 'The request body'
        );

        $response = $subject->handleRequest($request);

        self::assertSame(200, $response->getStatus());
        self::assertSame('Received body as type from attribute The request body', $response->getBody()->read());
    }

    public function testInvokeObjectDtoWidgetAttribute() : void {
        $controller = new CheckDtoController();
        $subject = new DtoControllerHandler($controller->checkWidgetDto(...), $this->container, new ErrorHandlerFactory());

        $request = new Request(
            $this->getMockBuilder(Client::class)->getMock(),
            HttpMethod::Get->value,
            Http::createFromString('http://example.com/some/path?foo=bar&bar=baz'),
            body: json_encode([
                'name' => 'Widget Name',
                'author' => [
                    'name' => 'Author Name',
                    'email' => 'author@example.com',
                    'website' => 'https://author.example.com'
                ],
                'created_at' => '2022-01-01 13:00:00'
            ])
        );

        $response = $subject->handleRequest($request);

        $expectedJson = '{"name":"Widget Name","author":{"name":"Author Name","email":"author@example.com","website":"https:\/\/author.example.com"},"createdAt":"2022-01-01T13:00:00+00:00"}';
        self::assertSame(200, $response->getStatus());
        self::assertSame('Received widget as Dto ' . $expectedJson, $response->getBody()->read());
    }
}