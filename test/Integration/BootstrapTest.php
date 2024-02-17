<?php

namespace Labrador\Test\Integration;

use Amp\Http\Server\DefaultErrorHandler;
use Amp\Http\Server\HttpServer;
use Amp\Http\Server\SocketHttpServer;
use Aura\Filter\FilterFactory;
use Cspray\AnnotatedContainer\AnnotatedContainer;
use Cspray\AnnotatedContainer\Bootstrap\Bootstrap as AnnotatedContainerBootstrap;
use Cspray\AnnotatedContainer\Bootstrap\ContainerCreatedObserver;
use Cspray\AnnotatedContainer\Definition\ContainerDefinition;
use Cspray\AnnotatedContainer\Profiles\ActiveProfiles;
use Cspray\StreamBufferIntercept\BufferIdentifier;
use Cspray\StreamBufferIntercept\StreamBuffer;
use Labrador\AsyncEvent\AmpEventEmitter;
use Labrador\AsyncEvent\EventEmitter;
use Labrador\DummyApp\Controller\CheckDtoController;
use Labrador\DummyApp\DummyMonologInitializer;
use Labrador\DummyApp\Middleware\BarMiddleware;
use Labrador\DummyApp\Middleware\BazMiddleware;
use Labrador\DummyApp\Middleware\FooMiddleware;
use Labrador\DummyApp\Middleware\QuxMiddleware;
use Labrador\Test\BootstrapAwareTestTrait;
use Labrador\Test\Helper\VfsDirectoryResolver;
use Labrador\Validation\AuraFilterRuleValidator;
use Labrador\Validation\Filter;
use Labrador\Web\Bootstrap;
use Labrador\Web\ErrorHandlerFactory;
use Labrador\Web\Middleware\Priority;
use Labrador\Web\Router\LoggingRouter;
use Labrador\Web\Router\Route;
use Labrador\Web\Router\Router;
use Monolog\Handler\TestHandler;
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

    private const ExpectedControllerCount = 3;

    use BootstrapAwareTestTrait;

    private static VirtualDirectory $vfs;

    private AnnotatedContainerBootstrap $containerBootstrap;

    private AnnotatedContainer $container;

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
        $this->containerBootstrap->addObserver(
            new class(fn(AnnotatedContainer $container) => $this->container = $container) implements ContainerCreatedObserver {
                public function __construct(
                    private readonly \Closure $setContainer
                ) {}

                public function notifyContainerCreated(ActiveProfiles $activeProfiles, ContainerDefinition $containerDefinition, AnnotatedContainer $container) : void {
                    ($this->setContainer)($container);
                }
            }
        );
    }

    protected function tearDown() : void {
        StreamBuffer::stopIntercepting($this->stdout);
        StreamBuffer::stopIntercepting($this->stderr);
    }

    public function testCorrectlyConfiguredAnnotatedContainerReturnsLogger() : void {
        $bootstrap = new Bootstrap($this->containerBootstrap, profiles: ['default', 'integration-test']);

        $bootstrap->bootstrapApplication();

        $logger = $this->container->get(LoggerInterface::class);

        self::assertInstanceOf(Logger::class, $logger);

        $logger->info('This is a test message');

        $handler = $this->container->get(DummyMonologInitializer::class)->testHandler;
        self::assertInstanceOf(TestHandler::class, $handler);
        self::assertTrue(
            $handler->hasInfoThatContains('This is a test message')
        );
    }

    public function testCorrectlyConfiguredAnnotatedContainerReturnsRouter() : void {
        $bootstrap = new Bootstrap($this->containerBootstrap, profiles: ['default', 'integration-test']);

        $bootstrap->bootstrapApplication();

        $router = $this->container->get(Router::class);

        self::assertInstanceOf(LoggingRouter::class, $router);
    }

    public function testCorrectlyConfiguredAnnotatedContainerReturnsEventEmitter() : void {
        $bootstrap = new Bootstrap($this->containerBootstrap, profiles: ['default', 'integration-test']);

        $bootstrap->bootstrapApplication();

        $emitter = $this->container->get(EventEmitter::class);

        self::assertInstanceOf(AmpEventEmitter::class, $emitter);
    }

    public function testCorrectlyConfiguredAnnotatedContainerReturnsErrorHandler() : void {
        $bootstrap = new Bootstrap($this->containerBootstrap, profiles: ['default', 'integration-test']);

        $bootstrap->bootstrapApplication();

        $errorHandlerFactory = $this->container->get(ErrorHandlerFactory::class);

        self::assertInstanceOf(DefaultErrorHandler::class, $errorHandlerFactory->createErrorHandler());
    }

    public function testCorrectlyConfiguredAnnotatedContainerHttpServer() : void {
        $bootstrap = new Bootstrap($this->containerBootstrap, profiles: ['default', 'integration-test']);

        $container = $bootstrap->bootstrapApplication();

        $httpServer = $this->container->get(HttpServer::class);

        self::assertInstanceOf(SocketHttpServer::class, $httpServer);
    }

    public function testCorrectlyConfiguredAnnotatedContainerRouterRoutes() : void {
        $bootstrap = new Bootstrap($this->containerBootstrap, profiles: ['default', 'integration-test']);

        $container = $bootstrap->bootstrapApplication();

        /** @var Router $router */
        $router = $this->container->get(Router::class);

        self::assertCount(self::ExpectedControllerCount, $router->getRoutes());
    }

    public function testApplicationAutowiringControllersLogged() : void {
        $bootstrap = new Bootstrap($this->containerBootstrap, profiles: ['default', 'integration-test']);

        $bootstrap->bootstrapApplication();

        $handler = $this->container->get(DummyMonologInitializer::class)->testHandler;
        self::assertInstanceOf(TestHandler::class, $handler);

        self::assertTrue(
            $handler->hasInfoThatContains('Autowiring route GET /hello/world to HelloWorld controller.')
        );
    }

    public static function expectedMiddlewareProvider() : array {
        return [
            BarMiddleware::class => [BarMiddleware::class, Priority::Low],
            BazMiddleware::class => [BazMiddleware::class, Priority::Medium],
            FooMiddleware::class => [FooMiddleware::class, Priority::High],
            QuxMiddleware::class => [QuxMiddleware::class, Priority::Critical]
        ];
    }

    /**
     * @dataProvider expectedMiddlewareProvider
     */
    public function testApplicationAutowiringApplicationMiddlewareLogged(string $middlewareClass, Priority $priority) : void {
        $bootstrap = new Bootstrap($this->containerBootstrap, profiles: ['default', 'integration-test']);
        $bootstrap->bootstrapApplication();

        $handler = $this->container->get(DummyMonologInitializer::class)->testHandler;
        self::assertInstanceOf(TestHandler::class, $handler);

        self::assertTrue(
            $handler->hasInfoThatContains('Adding ' . $middlewareClass . ' to application with ' . $priority->name . ' priority.')
        );
    }

}
