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
use Labrador\DummyApp\DummyLoggerFactory;
use Labrador\Test\BootstrapAwareTestTrait;
use Labrador\Test\Helper\VfsDirectoryResolver;
use Labrador\Web\Application\Bootstrap;
use Labrador\Web\Application\ErrorHandlerFactory;
use Labrador\Web\Autowire\RegisterControllerListener;
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

    private const EXPECTED_CONTROLLER_COUNT = 3;

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
        $emitter->addListener(new RegisterControllerListener());
        $this->containerBootstrap = AnnotatedContainerBootstrap::fromAnnotatedContainerConventions(
            new PhpDiContainerFactory($emitter),
            $emitter,
            directoryResolver: new VfsDirectoryResolver()
        );
        $emitter->addListener(
            new class(fn(AnnotatedContainer $container) => $this->container = $container) implements AfterContainerCreation {
                public function __construct(
                    private readonly \Closure $setContainer
                ) {
                }

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

        $handler = $this->container->get(DummyLoggerFactory::class)->testHandler;
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
        $bootstrap = Bootstrap::fromProvidedContainerBootstrap($this->containerBootstrap, profiles: ['default', 'integration-test', 'web']);

        $container = $bootstrap->bootstrapApplication();

        /** @var Router $router */
        $router = $this->container->get(Router::class);

        self::assertCount(self::EXPECTED_CONTROLLER_COUNT, $router->getRoutes());
    }

    public function testApplicationAutowiringControllersLogged() : void {
        $bootstrap = Bootstrap::fromProvidedContainerBootstrap($this->containerBootstrap, profiles: ['default', 'integration-test', 'web']);

        $bootstrap->bootstrapApplication();

        $handler = $this->container->get(DummyLoggerFactory::class)->testHandler;
        self::assertInstanceOf(TestHandler::class, $handler);

        self::assertTrue(
            $handler->hasInfoThatContains('Autowiring route GET /hello/world to MiddlewareHandler<HelloWorld, Amp\Http\Server\Session\SessionMiddleware, Labrador\Web\Session\CsrfAwareSessionMiddleware, Labrador\Web\Session\LockAndAutoCommitSessionMiddleware> controller.')
        );
    }
}
