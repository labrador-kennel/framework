<?php

namespace Labrador\Test\Unit\Controller;

use Amp\Http\Server\Driver\Client;
use Amp\Http\Server\Request;
use Amp\Http\Server\RequestBody;
use Amp\Http\Server\Session\DefaultSessionIdGenerator;
use Amp\Http\Server\Session\LocalSessionStorage;
use Amp\Http\Server\Session\Session;
use Amp\Sync\LocalKeyedMutex;
use Cspray\AnnotatedContainer\AnnotatedContainer;
use Cspray\StreamBufferIntercept\BufferIdentifier;
use Cspray\StreamBufferIntercept\StreamBuffer;
use Labrador\DummyApp\Controller\CheckDtoController;
use Labrador\Test\BootstrapAwareTestTrait;
use Labrador\Test\Unit\Stub\BadDtoController;
use Labrador\Web\Controller\DtoController;
use Labrador\Web\Exception\InvalidDtoAttribute;
use Labrador\Web\Exception\InvalidType;
use Labrador\Web\HttpMethod;
use League\Uri\Http;
use org\bovigo\vfs\vfsStream as VirtualFilesystem;
use org\bovigo\vfs\vfsStreamDirectory as VirtualDirectory;
use PHPUnit\Framework\TestCase;
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;
use function Cspray\AnnotatedContainer\autowiredParams;
use function Cspray\AnnotatedContainer\rawParam;

final class DtoControllerHandlerTest extends TestCase {

    use BootstrapAwareTestTrait;

    private AnnotatedContainer $container;
    private VirtualDirectory $vfs;
    private $streamFilter;

    private BufferIdentifier $stdout;

    private BufferIdentifier $stderr;

    public static function setUpBeforeClass() : void {
        StreamBuffer::register();
    }

    protected function setUp() : void {
        $this->stdout = StreamBuffer::intercept(STDOUT);
        $this->stderr = StreamBuffer::intercept(STDERR);
        $this->vfs = VirtualFilesystem::setup();
        VirtualFilesystem::newFile('annotated-container.xml')
            ->withContent(self::getDefaultConfiguration())
            ->at($this->vfs);
        $this->container = self::getContainer(['default', 'unit-test']);
    }

    protected function tearDown() : void {
        StreamBuffer::stopIntercepting($this->stdout);
        StreamBuffer::stopIntercepting($this->stderr);
    }

    private function subject(\Closure $closure, string $description) : DtoController {
        $handler = $this->container->make(
            DtoController::class,
            autowiredParams(
                rawParam('closure', $closure),
                rawParam('description', $description),
            )
        );
        assert($handler instanceof DtoController);
        return $handler;
    }

    public function testInvokeObjectHeadersDto() : void {
        $controller = new CheckDtoController();
        $subject = $this->subject($controller->checkHeaders(...), 'checkHeaders');

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
        $subject = $this->subject($controller->checkMethod(...), 'checkMethod');

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
        $subject = $this->subject($controller->checkHeadersParamName(...), 'checkHeadersParamName');

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
        $subject = $this->subject($controller->checkSingleHeaderArray(...), 'checkSingleHeaderArray');

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
        $subject = $this->subject($controller->checkSingleHeaderString(...), 'checkSingleHeaderString');

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
        $subject = $this->subject($controller->checkUrl(...), 'checkUrl');

        $request = new Request(
            $this->getMockBuilder(Client::class)->getMock(),
            HttpMethod::Get->value,
            Http::createFromString('http://example.com'),
        );

        $response = $subject->handleRequest($request);

        self::assertSame(200, $response->getStatus());
        self::assertSame('Received UriInterface http://example.com', $response->getBody()->read());
    }

    public function testInvokeObjectQueryQueryInterfaceDto() : void {
        $controller = new CheckDtoController();
        $subject = $this->subject($controller->checkQueryInterface(...), 'checkQueryInterface');

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
        $subject = $this->subject($controller->checkQueryComponent(...), 'checkQueryComponent');

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
        $subject = $this->subject($controller->checkRouteParam(...), 'checkRouteParam');

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
        $subject = $this->subject($controller->checkRouteParamUuid(...), 'checkRouteParamUuid');

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
        $subject = $this->subject($controller->checkUriInjectedByType(...), 'checkUriInjectedByType');

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
        $subject = $this->subject($controller->checkQueryInterfaceByType(...), 'checkQueryInterfaceByType');

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
        $subject = $this->subject($controller->checkQueryByType(...), 'checkQueryByType');

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
        $subject = $this->subject($controller->checkBodyByType(...), 'checkBodyByType');

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
        $subject = $this->subject($controller->checkBodyAsString(...), 'checkBodyAsString');

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
        $subject = $this->subject($controller->checkBodyAsTypeAttribute(...),'checkBodyAsTypeAttribute');

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
        $subject = $this->subject($controller->checkWidgetDto(...), 'checkWidgetDto');

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
                'created_at' => '2022-01-01T13:00:00+00:00'
            ], JSON_THROW_ON_ERROR)
        );

        $response = $subject->handleRequest($request);

        $expectedJson = '{"name":"Widget Name","author":{"name":"Author Name","email":"author@example.com","website":"https:\/\/author.example.com"},"createdAt":"2022-01-01T13:00:00+00:00"}';
        self::assertSame(200, $response->getStatus());
        self::assertSame('Received widget as Dto ' . $expectedJson, $response->getBody()->read());
    }

    public function testInvokeObjectRequestByType() : void {
        $controller = new CheckDtoController();
        $subject = $this->subject($controller->checkRequest(...), 'checkRequest');

        $request = new Request(
            $this->getMockBuilder(Client::class)->getMock(),
            HttpMethod::Get->value,
            Http::createFromString('http://example.com/some/path?foo=bar&bar=baz')
        );

        $response = $subject->handleRequest($request);

        self::assertSame(200, $response->getStatus());
        self::assertSame('Received Request instance for /some/path', $response->getBody()->read());
    }

    public function testCheckSessionByType() : void {
        $controller = new CheckDtoController();
        $subject = $this->subject($controller->checkSessionByType(...), 'checkSession');

        $request = new Request(
            $this->getMockBuilder(Client::class)->getMock(),
            HttpMethod::Get->value,
            Http::createFromString('http://example.com/some/path')
        );
        $request->setAttribute(Session::class, new Session(
            new LocalKeyedMutex(),
            new LocalSessionStorage(),
            new DefaultSessionIdGenerator(),
            null
        ));

        $response = $subject->handleRequest($request);

        self::assertSame(200, $response->getStatus());
        self::assertSame('Able to access session', $response->getBody()->read());
    }

    // ========================================== Test Bad Attributes ==================================================

    public function testInvokeObjectWithBadHeaders() : void {
        $controller = new BadDtoController();

        $this->expectException(InvalidType::class);
        $this->expectExceptionMessage('The parameter "headers" on ' . BadDtoController::class . '::checkImplicitHeadersDto is marked with a #[Headers] Attribute but is not type-hinted as an array.');

        $this->subject($controller->checkImplicitHeadersDto(...), BadDtoController::class . '::checkImplicitHeadersDto');
    }

    public function testInvokeObjectWithBadMethod() : void {
        $controller = new BadDtoController();

        $this->expectException(InvalidType::class);
        $this->expectExceptionMessage('The parameter "method" on ' . BadDtoController::class . '::checkMethodInt is marked with a #[Method] Attribute but is not type-hinted as a string.');

        $this->subject($controller->checkMethodInt(...), BadDtoController::class . '::checkMethodInt');
    }

    public function testInvokeObjectWithBadHeader() : void {
        $controller = new BadDtoController();

        $this->expectException(InvalidType::class);
        $this->expectExceptionMessage('The parameter "token" on ' . BadDtoController::class . '::checkSingleHeaderNotArrayOrString is marked with a #[Header] Attribute but is not type-hinted as an array or string.');

        $this->subject($controller->checkSingleHeaderNotArrayOrString(...), BadDtoController::class . '::checkSingleHeaderNotArrayOrString');
    }

    public function testInvokeObjectWithBadRouteParam() : void {
        $controller = new BadDtoController();

        self::expectException(InvalidType::class);
        self::expectExceptionMessage('The parameter "foo" on ' . BadDtoController::class . '::checkRouteParamNotUuidOrString is marked with a #[RouteParam] Attribute but is not type-hinted as a string or ' . UuidInterface::class . '.');

        $this->subject($controller->checkRouteParamNotUuidOrString(...), BadDtoController::class . '::checkRouteParamNotUuidOrString');
    }

    public function testInvokeObjectWithBadBody() : void {
        $controller = new BadDtoController();

        self::expectException(InvalidType::class);
        self::expectExceptionMessage('The parameter "body" on ' . BadDtoController::class . '::checkBodyNotRequestBodyOrString is marked with a #[Body] Attribute but is not type-hinted as a string or ' . RequestBody::class . '.');

        $this->subject($controller->checkBodyNotRequestBodyOrString(...), BadDtoController::class . '::checkBodyNotRequestBodyOrString');
    }

    public function testInvokeObjectWithBadImplicitDto() : void {
        $controller = new BadDtoController();

        self::expectException(InvalidType::class);
        self::expectExceptionMessage('The parameter "widget" on ' . BadDtoController::class . '::checkImplicitDto is marked with a #[Dto] Attribute but is not type-hinted with a class type.');

        $this->subject($controller->checkImplicitDto(...), BadDtoController::class . '::checkImplicitDto');
    }

    public function testInvokeObjectWithBadNonClassDto() : void {
        $controller = new BadDtoController();

        self::expectException(InvalidType::class);
        self::expectExceptionMessage('The parameter "bar" on ' . BadDtoController::class . '::checkNonClassDto is marked with a #[Dto] Attribute but is not type-hinted with a class type.');

        $this->subject($controller->checkNonClassDto(...), BadDtoController::class . '::checkNonClassDto');
    }

    public function testInvokeObjectWithMultipleDtoAttributes() : void {
        $controller = new BadDtoController();

        self::expectException(InvalidDtoAttribute::class);
        self::expectExceptionMessage('The parameter "duo" on ' . BadDtoController::class . '::checkMultipleAttributes declares multiple DTO Attributes but MUST contain only 1.');

        $this->subject($controller->checkMultipleAttributes(...), BadDtoController::class . '::checkMultipleAttributes');
    }
}