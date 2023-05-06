<?php

namespace Labrador\Http;

use Cspray\AnnotatedContainer\Bootstrap\Bootstrap as AnnotatedContainerBootstrap;
use Labrador\Http\Application\Application;
use Labrador\Http\Logging\LoggerFactory;
use Labrador\Http\Logging\LoggerType;
use PackageVersions\Versions;
use Psr\Log\LoggerInterface;
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
        $memoryLimit = ini_get('memory_limit');
        getStdout()->write(file_get_contents(__DIR__ . '/../resources/ascii/labrador.txt') . PHP_EOL);
        getStdout()->write('PHP Version: ' . PHP_VERSION . PHP_EOL);
        getStdout()->write('Labrador HTTP Version: ' . Versions::getVersion('labrador-kennel/http') . PHP_EOL);
        getStdout()->write('Annotated Container Version: ' . Versions::getVersion('cspray/annotated-container') . PHP_EOL);
        getStdout()->write('Amp HTTP Server Version: ' . Versions::getVersion('amphp/http-server') . PHP_EOL);
        getStdout()->write('Revolt Loop Driver: ' . EventLoop::getDriver()::class . PHP_EOL);
        getStdout()->write(PHP_EOL);
        getStdout()->write(sprintf('OS: %s %s %s%s', php_uname('s'), php_uname('r'), php_uname('m'), PHP_EOL));
        getStdout()->write('Host Name: ' . php_uname('n') . PHP_EOL);
        getStdout()->write('User: ' . get_current_user() . PHP_EOL);
        getStdout()->write('Process ID: ' . getmypid() . PHP_EOL);
        getStdout()->write('Memory Limit: ' . $memoryLimit . PHP_EOL);
        getStdout()->write(PHP_EOL);

        $container = $this->bootstrap->bootstrapContainer(profiles: $this->profiles);

        /** @var LoggerInterface $logger */
        $logger = $container->get(LoggerFactory::class)->createLogger(LoggerType::Application);
        if ($memoryLimit !== '-1') {
            $logger->warning('Running Labrador with a hard memory limit is not recommended! Please check your PHP ini settings and set the memory_limit value to "-1"!');
        }

        $app = $container->get(Application::class);
        assert($app instanceof Application);

        return new BootstrapResults($app, $container);
    }

}