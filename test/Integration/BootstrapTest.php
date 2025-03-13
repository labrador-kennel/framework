<?php

namespace Labrador\Test\Integration;

use Amp\Http\Server\DefaultErrorHandler;
use Amp\Http\Server\HttpServer;
use Amp\Http\Server\SocketHttpServer;
use Cspray\AnnotatedContainer\AnnotatedContainer;
use Cspray\AnnotatedContainer\Bootstrap\Bootstrap as AnnotatedContainerBootstrap;
use Cspray\AnnotatedContainer\ContainerFactory\PhpDiContainerFactory;
use Cspray\AnnotatedContainer\Definition\ContainerDefinition;
use Cspray\AnnotatedContainer\Event\Emitter as AnnotatedContainerEmitter;
use Cspray\AnnotatedContainer\Event\Listener\ContainerFactory\AfterContainerCreation;
use Cspray\AnnotatedContainer\Profiles;
use Cspray\StreamBufferIntercept\BufferIdentifier;
use Cspray\StreamBufferIntercept\StreamBuffer;
use Labrador\AsyncEvent\AmpEmitter;
use Labrador\AsyncEvent\Emitter;
use Labrador\DummyApp\DummyMonologInitializer;
use Labrador\DummyApp\Middleware\BarMiddleware;
use Labrador\DummyApp\Middleware\BazMiddleware;
use Labrador\DummyApp\Middleware\FooMiddleware;
use Labrador\DummyApp\Middleware\QuxMiddleware;
use Labrador\Test\BootstrapAwareTestTrait;
use Labrador\Test\Helper\VfsDirectoryResolver;
use Labrador\Web\Application\Bootstrap;
use Labrador\Web\Application\ErrorHandlerFactory;
use Labrador\Web\Autowire\RegisterControllerAndMiddlewareListener;
use Labrador\Web\Middleware\Priority;
use Labrador\Web\Router\LoggingRouter;
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
        VirtualFilesystem::newFile('annotated-container.xml')
            ->withContent(self::getDefaultConfiguration())
            ->at(self::$vfs);
    }

    protected function setUp() : void {
        parent::setUp();
        $this->stdout = StreamBuffer::intercept(STDOUT);
        $this->stderr = StreamBuffer::intercept(STDERR);
        $emitter = new AnnotatedContainerEmitter();
        $emitter->addListener(new RegisterControllerAndMiddlewareListener());
        $this->containerBootstrap = AnnotatedContainerBootstrap::fromAnnotatedContainerConventions(
            new PhpDiContainerFactory($emitter),
            $emitter,
            directoryResolver: new VfsDirectoryResolver()
        );
        $emitter->addListener(
            new class(fn(AnnotatedContainer $container) => $this->container = $container) implements AfterContainerCreation {
                public function __construct(
                    private readonly \Closure $setContainer
                ) {}

                public function handleAfterContainerCreation(
                    Profiles $profiles,
                    ContainerDefinition $containerDefinition,
                    AnnotatedContainer $container
                ) : void {
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
        $bootstrap = Bootstrap::fromProvidedContainerBootstrap($this->containerBootstrap, profiles: ['default', 'integration-test']);

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
        $bootstrap = Bootstrap::fromProvidedContainerBootstrap($this->containerBootstrap, profiles: ['default', 'integration-test']);

        $bootstrap->bootstrapApplication();

        $router = $this->container->get(Router::class);

        self::assertInstanceOf(LoggingRouter::class, $router);
    }

    public function testCorrectlyConfiguredAnnotatedContainerReturnsEventEmitter() : void {
        $bootstrap = Bootstrap::fromProvidedContainerBootstrap($this->containerBootstrap, profiles: ['default', 'integration-test']);

        $bootstrap->bootstrapApplication();

        $emitter = $this->container->get(Emitter::class);

        self::assertInstanceOf(AmpEmitter::class, $emitter);
    }

    public function testCorrectlyConfiguredAnnotatedContainerReturnsErrorHandler() : void {
        $bootstrap = Bootstrap::fromProvidedContainerBootstrap($this->containerBootstrap, profiles: ['default', 'integration-test']);

        $bootstrap->bootstrapApplication();

        $errorHandlerFactory = $this->container->get(ErrorHandlerFactory::class);

        self::assertInstanceOf(DefaultErrorHandler::class, $errorHandlerFactory->createErrorHandler());
    }

    public function testCorrectlyConfiguredAnnotatedContainerHttpServer() : void {
        $bootstrap = Bootstrap::fromProvidedContainerBootstrap($this->containerBootstrap, profiles: ['default', 'integration-test']);

        $container = $bootstrap->bootstrapApplication();

        $httpServer = $this->container->get(HttpServer::class);

        self::assertInstanceOf(SocketHttpServer::class, $httpServer);
    }

    public function testCorrectlyConfiguredAnnotatedContainerRouterRoutes() : void {
        $bootstrap = Bootstrap::fromProvidedContainerBootstrap($this->containerBootstrap, profiles: ['default', 'integration-test']);

        $container = $bootstrap->bootstrapApplication();

        /** @var Router $router */
        $router = $this->container->get(Router::class);

        self::assertCount(self::ExpectedControllerCount, $router->getRoutes());
    }

    public function testApplicationAutowiringControllersLogged() : void {
        $bootstrap = Bootstrap::fromProvidedContainerBootstrap($this->containerBootstrap, profiles: ['default', 'integration-test']);

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
        $bootstrap = Bootstrap::fromProvidedContainerBootstrap($this->containerBootstrap, profiles: ['default', 'integration-test']);
        $bootstrap->bootstrapApplication();

        $handler = $this->container->get(DummyMonologInitializer::class)->testHandler;
        self::assertInstanceOf(TestHandler::class, $handler);

        self::assertTrue(
            $handler->hasInfoThatContains('Adding ' . $middlewareClass . ' to application with ' . $priority->name . ' priority.')
        );
    }



}
