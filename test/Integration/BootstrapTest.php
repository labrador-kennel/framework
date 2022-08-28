<?php

namespace Cspray\Labrador\Http\Test\Integration;

use Amp\Http\Server\DefaultErrorHandler;
use Amp\Http\Server\ErrorHandler;
use Amp\Http\Server\HttpServer;
use Amp\Http\Server\SocketHttpServer;
use Cspray\AnnotatedContainer\Bootstrap\Bootstrap as AnnotatedContainerBootstrap;
use Cspray\Labrador\AsyncEvent\AmpEventEmitter;
use Cspray\Labrador\AsyncEvent\EventEmitter;
use Cspray\Labrador\Http\Bootstrap;
use Cspray\Labrador\Http\ErrorHandlerFactory;
use Cspray\Labrador\Http\Router\FastRouteRouter;
use Cspray\Labrador\Http\Router\Router;
use Cspray\Labrador\Http\Test\Helper\StreamBuffer;
use Cspray\Labrador\Http\Test\Helper\VfsDirectoryResolver;
use Cspray\Labrador\HttpDummyApp\AppMiddleware\BarMiddleware;
use Cspray\Labrador\HttpDummyApp\AppMiddleware\BazMiddleware;
use Cspray\Labrador\HttpDummyApp\AppMiddleware\FooMiddleware;
use Cspray\Labrador\HttpDummyApp\AppMiddleware\QuxMiddleware;
use Monolog\Logger;
use org\bovigo\vfs\vfsStream as VirtualFilesystem;
use org\bovigo\vfs\vfsStreamDirectory as VirtualDirectory;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
 * Ensures that a properly configured annotated-container.xml file will result in the appropriate services
 * being available in the container.
 */
class BootstrapTest extends TestCase {

    private VirtualDirectory $vfs;

    private AnnotatedContainerBootstrap $containerBootstrap;

    protected function setUp() : void {
        parent::setUp();
        $this->vfs = VirtualFilesystem::setup();
        if (!in_array('test.stream.buffer', stream_get_filters())) {
            self::assertTrue(stream_filter_register('test.stream.buffer', StreamBuffer::class));
        }
        $this->streamFilter = stream_filter_append(STDOUT, 'test.stream.buffer');
        self::assertIsResource($this->streamFilter);
        self::assertEmpty(StreamBuffer::getBuffer());

        $this->containerBootstrap = new AnnotatedContainerBootstrap(directoryResolver: new VfsDirectoryResolver());
    }

    protected function tearDown() : void {
        parent::tearDown();
        StreamBuffer::clearBuffer();
        self::assertTrue(stream_filter_remove($this->streamFilter));
    }

    private function configureAnnotatedContainer() : void {
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
            ->at($this->vfs);

    }

    public function testCorrectlyConfiguredAnnotatedContainerReturnsLogger() : void {
        $this->configureAnnotatedContainer();

        $bootstrap = new Bootstrap($this->containerBootstrap, profiles: ['default', 'integration-test']);

        $container = $bootstrap->bootstrapApplication()->container;

        $logger = $container->get(LoggerInterface::class);

        self::assertInstanceOf(Logger::class, $logger);

        $logger->info('This is a test message');
        self::assertStringContainsString('This is a test message', StreamBuffer::getBuffer());
    }

    public function testCorrectlyConfiguredAnnotatedContainerReturnsRouter() : void {
        $this->configureAnnotatedContainer();

        $bootstrap = new Bootstrap($this->containerBootstrap, profiles: ['default', 'integration-test']);

        $container = $bootstrap->bootstrapApplication()->container;

        $router = $container->get(Router::class);

        self::assertInstanceOf(FastRouteRouter::class, $router);
    }

    public function testCorrectlyConfiguredAnnotatedContainerReturnsEventEmitter() : void {
        $this->configureAnnotatedContainer();

        $bootstrap = new Bootstrap($this->containerBootstrap, profiles: ['default', 'integration-test']);

        $container = $bootstrap->bootstrapApplication()->container;

        $emitter = $container->get(EventEmitter::class);

        self::assertInstanceOf(AmpEventEmitter::class, $emitter);
    }

    public function testCorrectlyConfiguredAnnotatedContainerReturnsErrorHandler() : void {
        $this->configureAnnotatedContainer();

        $bootstrap = new Bootstrap($this->containerBootstrap, profiles: ['default', 'integration-test']);

        $container = $bootstrap->bootstrapApplication()->container;

        $errorHandler = $container->get(ErrorHandler::class);

        self::assertInstanceOf(DefaultErrorHandler::class, $errorHandler);
    }

    public function testCorrectlyConfiguredAnnotatedContainerAllowsErrorHandlerOverwriting() : void {
        $this->configureAnnotatedContainer();

        $errorHandler = $this->getMockBuilder(ErrorHandler::class)->getMock();
        $bootstrap = new Bootstrap(
            $this->containerBootstrap,
            profiles: ['default', 'integration-test'],
            errorHandler: $errorHandler
        );

        $container = $bootstrap->bootstrapApplication()->container;

        $actual = $container->get(ErrorHandler::class);

        self::assertSame($errorHandler, $actual);
    }

    public function testCorrectlyConfiguredAnnotatedContainerHttpServer() : void {
        $this->configureAnnotatedContainer();

        $bootstrap = new Bootstrap($this->containerBootstrap, profiles: ['default', 'integration-test']);

        $container = $bootstrap->bootstrapApplication()->container;

        $httpServer = $container->get(HttpServer::class);

        self::assertInstanceOf(SocketHttpServer::class, $httpServer);
    }

    public function testCorrectlyConfiguredAnnotatedContainerRouterRoutes() : void {
        $this->configureAnnotatedContainer();

        $bootstrap = new Bootstrap($this->containerBootstrap, profiles: ['default', 'integration-test']);

        $container = $bootstrap->bootstrapApplication()->container;

        /** @var Router $router */
        $router = $container->get(Router::class);

        self::assertCount(1, $router->getRoutes());
    }

    public function testApplicationAutowiringStartedLogged() : void {
        $this->configureAnnotatedContainer();

        $bootstrap = new Bootstrap($this->containerBootstrap, profiles: ['default', 'integration-test']);

        $bootstrap->bootstrapApplication()->container;

        $logLines = explode(PHP_EOL, trim(StreamBuffer::getBuffer()));

        self::assertGreaterThan(1, count($logLines));

        self::assertStringContainsString('Container created, beginning to autowire services.', $logLines[0]);
    }

    public function testApplicationAutowiringControllersLogged() : void {
        $this->configureAnnotatedContainer();

        $bootstrap = new Bootstrap($this->containerBootstrap, profiles: ['default', 'integration-test']);

        $bootstrap->bootstrapApplication()->container;

        self::assertStringContainsString('Autowiring route GET /hello/world to HelloWorld controller.', StreamBuffer::getBuffer());
    }

    public function testApplicationAutowiringApplicationMiddlewareLogged() : void {
        $this->configureAnnotatedContainer();

        $bootstrap = new Bootstrap($this->containerBootstrap, profiles: ['default', 'integration-test']);
        $bootstrap->bootstrapApplication();

        self::assertStringContainsString('Adding ' . BarMiddleware::class . ' to application with Low priority.', StreamBuffer::getBuffer());
        self::assertStringContainsString('Adding ' . BazMiddleware::class . ' to application with Medium priority.', StreamBuffer::getBuffer());
        self::assertStringContainsString('Adding ' . FooMiddleware::class . ' to application with High priority.', StreamBuffer::getBuffer());
        self::assertStringContainsString('Adding ' . QuxMiddleware::class . ' to application with Critical priority.', StreamBuffer::getBuffer());
    }
}