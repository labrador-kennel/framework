<?php

namespace Labrador\Http;

use Amp\Http\Server\ErrorHandler;
use Cspray\AnnotatedContainer\AnnotatedContainerVersion;
use Cspray\AnnotatedContainer\Bootstrap\Bootstrap as AnnotatedContainerBootstrap;
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
        getStdout()->write('Labrador HTTP Version: dev-main' . PHP_EOL);
        getStdout()->write('Annotated Container Version: ' . AnnotatedContainerVersion::getVersion() . PHP_EOL);
        getStdout()->write('Amp HTTP Server Version: v3.0.0' . PHP_EOL . PHP_EOL);

        $container = $this->bootstrap->bootstrapContainer(profiles: $this->profiles);

        $app = $container->get(Application::class);
        assert($app instanceof Application);

        return new BootstrapResults($app, $container);
    }

}