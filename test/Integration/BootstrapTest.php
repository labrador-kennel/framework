<?php

namespace Labrador\Test\Integration;

use Amp\Http\Server\DefaultErrorHandler;
use Amp\Http\Server\HttpServer;
use Amp\Http\Server\SocketHttpServer;
use Cspray\AnnotatedContainer\Bootstrap\Bootstrap as AnnotatedContainerBootstrap;
use Cspray\StreamBufferIntercept\BufferIdentifier;
use Cspray\StreamBufferIntercept\StreamBuffer;
use Labrador\AsyncEvent\AmpEventEmitter;
use Labrador\AsyncEvent\EventEmitter;
use Labrador\DummyApp\Controller\CheckDtoController;
use Labrador\DummyApp\Middleware\BarMiddleware;
use Labrador\DummyApp\Middleware\BazMiddleware;
use Labrador\DummyApp\Middleware\FooMiddleware;
use Labrador\DummyApp\Middleware\QuxMiddleware;
use Labrador\Test\BootstrapAwareTestTrait;
use Labrador\Test\Helper\VfsDirectoryResolver;
use Labrador\Web\Bootstrap;
use Labrador\Web\ErrorHandlerFactory;
use Labrador\Web\Router\LoggingRouter;
use Labrador\Web\Router\Route;
use Labrador\Web\Router\Router;
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

    private const ExpectedControllerCount = 29;

    use BootstrapAwareTestTrait;

    private static VirtualDirectory $vfs;

    private AnnotatedContainerBootstrap $containerBootstrap;

    private BufferIdentifier $stdout;
    private BufferIdentifier $stderr;

    public static function setUpBeforeClass() : void {
        StreamBuffer::register();
        self::$vfs = VirtualFilesystem::setup();
        VirtualFilesystem::newDirectory('.annotated-container-cache')->at(self::$vfs);
        VirtualFilesystem::newFile('annotated-container.xml')
            ->withContent(self::getDefaultConfiguration())
            ->at(self::$vfs);
    }

    protected function setUp() : void {
        parent::setUp();
        $this->stdout = StreamBuffer::intercept(STDOUT);
        $this->stderr = StreamBuffer::intercept(STDERR);
        $this->containerBootstrap = new AnnotatedContainerBootstrap(directoryResolver: new VfsDirectoryResolver());
    }

    protected function tearDown() : void {
        StreamBuffer::stopIntercepting($this->stdout);
        StreamBuffer::stopIntercepting($this->stderr);
    }

    public function testCorrectlyConfiguredAnnotatedContainerReturnsLogger() : void {
        $bootstrap = new Bootstrap($this->containerBootstrap, profiles: ['default', 'integration-test']);

        $container = $bootstrap->bootstrapApplication()->container;

        $logger = $container->get(LoggerInterface::class);

        self::assertInstanceOf(Logger::class, $logger);

        $logger->info('This is a test message');
        self::assertStringContainsString('This is a test message', StreamBuffer::output($this->stdout));
    }

    public function testCorrectlyConfiguredAnnotatedContainerReturnsRouter() : void {
        $bootstrap = new Bootstrap($this->containerBootstrap, profiles: ['default', 'integration-test']);

        $container = $bootstrap->bootstrapApplication()->container;

        $router = $container->get(Router::class);

        self::assertInstanceOf(LoggingRouter::class, $router);
    }

    public function testCorrectlyConfiguredAnnotatedContainerReturnsEventEmitter() : void {
        $bootstrap = new Bootstrap($this->containerBootstrap, profiles: ['default', 'integration-test']);

        $container = $bootstrap->bootstrapApplication()->container;

        $emitter = $container->get(EventEmitter::class);

        self::assertInstanceOf(AmpEventEmitter::class, $emitter);
    }

    public function testCorrectlyConfiguredAnnotatedContainerReturnsErrorHandler() : void {
        $bootstrap = new Bootstrap($this->containerBootstrap, profiles: ['default', 'integration-test']);

        $container = $bootstrap->bootstrapApplication()->container;

        $errorHandlerFactory = $container->get(ErrorHandlerFactory::class);

        self::assertInstanceOf(DefaultErrorHandler::class, $errorHandlerFactory->createErrorHandler());
    }

    public function testCorrectlyConfiguredAnnotatedContainerHttpServer() : void {
        $bootstrap = new Bootstrap($this->containerBootstrap, profiles: ['default', 'integration-test']);

        $container = $bootstrap->bootstrapApplication()->container;

        $httpServer = $container->get(HttpServer::class);

        self::assertInstanceOf(SocketHttpServer::class, $httpServer);
    }

    public function testCorrectlyConfiguredAnnotatedContainerRouterRoutes() : void {
        $bootstrap = new Bootstrap($this->containerBootstrap, profiles: ['default', 'integration-test']);

        $container = $bootstrap->bootstrapApplication()->container;

        /** @var Router $router */
        $router = $container->get(Router::class);

        self::assertCount(self::ExpectedControllerCount, $router->getRoutes());
    }

    public function testApplicationAutowiringControllersLogged() : void {
        $bootstrap = new Bootstrap($this->containerBootstrap, profiles: ['default', 'integration-test']);

        $bootstrap->bootstrapApplication()->container;

        self::assertStringContainsString('Autowiring route GET /hello/world to HelloWorld controller.', StreamBuffer::output($this->stdout));
    }

    public function testApplicationAutowiringApplicationMiddlewareLogged() : void {
        $bootstrap = new Bootstrap($this->containerBootstrap, profiles: ['default', 'integration-test']);
        $bootstrap->bootstrapApplication();

        self::assertStringContainsString('Adding ' . BarMiddleware::class . ' to application with Low priority.', StreamBuffer::output($this->stdout));
        self::assertStringContainsString('Adding ' . BazMiddleware::class . ' to application with Medium priority.', StreamBuffer::output($this->stdout));
        self::assertStringContainsString('Adding ' . FooMiddleware::class . ' to application with High priority.', StreamBuffer::output($this->stdout));
        self::assertStringContainsString('Adding ' . QuxMiddleware::class . ' to application with Critical priority.', StreamBuffer::output($this->stdout));
    }

    public function testDtoControllerRoutedWithCorrectControllerDescription() : void {
        $bootstrap = new Bootstrap($this->containerBootstrap, profiles: ['default', 'integration-test']);
        $container = $bootstrap->bootstrapApplication()->container;

        $router = $container->get(Router::class);

        self::assertInstanceOf(Router::class, $router);

        $routes = array_filter($router->getRoutes(), fn(Route $route) => $route->requestMapping->getPath() === '/dto/headers');

        self::assertCount(1, $routes);

        $route = array_shift($routes);

        self::assertSame(sprintf('DtoHandler<%s::checkHeaders>', CheckDtoController::class), $route->controller->toString());
    }

    public function testDtoControllerGetRouteLogged() : void {
        $bootstrap = new Bootstrap($this->containerBootstrap, profiles: ['default', 'integration-test']);
        $bootstrap->bootstrapApplication();

        self::assertStringContainsString(
            sprintf(
                'labrador.app.INFO: Autowiring route GET /dto/headers to DtoHandler<%s::checkHeaders> controller.',
                CheckDtoController::class
            ),
            StreamBuffer::output($this->stdout)
        );
    }

    public function testDtoControllerPostRouteLogged() : void {
        $bootstrap = new Bootstrap($this->containerBootstrap, profiles: ['default', 'integration-test']);
        $bootstrap->bootstrapApplication();

        self::assertStringContainsString(
            sprintf(
                'labrador.app.INFO: Autowiring route POST /dto/method to DtoHandler<%s::checkMethod> controller.',
                CheckDtoController::class
            ),
            StreamBuffer::output($this->stdout)
        );
    }

    public function testDtoControllerPutRouteLogged() : void {
        $bootstrap = new Bootstrap($this->containerBootstrap, profiles: ['default', 'integration-test']);
        $bootstrap->bootstrapApplication();

        self::assertStringContainsString(
            sprintf(
                'labrador.app.INFO: Autowiring route PUT /dto/url to DtoHandler<%s::checkUrl> controller.',
                CheckDtoController::class
            ),
            StreamBuffer::output($this->stdout)
        );
    }

    public function testDtoControllerDeleteRouteLogged() : void {
        $bootstrap = new Bootstrap($this->containerBootstrap, profiles: ['default', 'integration-test']);
        $bootstrap->bootstrapApplication();

        self::assertStringContainsString(
            sprintf(
                'labrador.app.INFO: Autowiring route DELETE /dto/widget/{id} to DtoHandler<%s::deleteWidget> controller.',
                CheckDtoController::class
            ),
            StreamBuffer::output($this->stdout)
        );
    }
}