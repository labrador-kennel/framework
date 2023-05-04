<?php

namespace Labrador\Http;

use Cspray\AnnotatedContainer\Bootstrap\Bootstrap as AnnotatedContainerBootstrap;
use Labrador\Http\Application\Application;
use PackageVersions\Versions;
use Revolt\EventLoop;
use function Amp\ByteStream\getStdout;

final class Bootstrap {

    public function __construct(
        private readonly AnnotatedContainerBootstrap $bootstrap,
        /**
         * @var list<string> $profiles
         */
        private readonly array $profiles = ['default'],
    ) {}

    public function bootstrapApplication() : BootstrapResults {
        getStdout()->write(file_get_contents(__DIR__ . '/../resources/ascii/labrador.txt') . PHP_EOL);
        getStdout()->write('PHP Version: ' . PHP_VERSION . PHP_EOL);
        getStdout()->write('Labrador HTTP Version: ' . Versions::getVersion('labrador-kennel/http') . PHP_EOL);
        getStdout()->write('Annotated Container Version: ' . Versions::getVersion('cspray/annotated-container') . PHP_EOL);
        getStdout()->write('Amp HTTP Server Version: ' . Versions::getVersion('amphp/http-server') . PHP_EOL);
        getStdout()->write('Revolt Loop Driver: ' . EventLoop::getDriver()::class . PHP_EOL . PHP_EOL);

        $container = $this->bootstrap->bootstrapContainer(profiles: $this->profiles);

        $app = $container->get(Application::class);
        assert($app instanceof Application);

        return new BootstrapResults($app, $container);
    }

}