<?php

namespace Labrador\Web;

use Cspray\AnnotatedContainer\Bootstrap\Bootstrap as AnnotatedContainerBootstrap;
use Labrador\Web\Application\Application;
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
        $memoryLimit = ini_get('memory_limit');
        getStdout()->write($this->labradorAscii() . PHP_EOL);
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

        $app = $container->get(Application::class);
        assert($app instanceof Application);

        return new BootstrapResults($app, $container);
    }

    private function labradorAscii() : string {
        return <<<ASCII
______      ______               _________
___  /_____ ___  /______________ ______  /_____________
__  /_  __ `/_  __ \_  ___/  __ `/  __  /_  __ \_  ___/
_  / / /_/ /_  /_/ /  /   / /_/ // /_/ / / /_/ /  /
/_/  \__,_/ /_.___//_/    \__,_/ \__,_/  \____//_/

ASCII;
    }

}