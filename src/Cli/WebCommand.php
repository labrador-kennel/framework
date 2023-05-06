<?php declare(strict_types=1);

namespace Labrador\Http\Cli;

use Amp\Log\ConsoleFormatter;
use Amp\Log\StreamHandler;
use Amp\Process\Process;
use Cspray\AnnotatedContainer\Bootstrap\Bootstrap;
use Cspray\AnnotatedContainer\Cli\Command;
use Cspray\AnnotatedContainer\Cli\Input;
use Cspray\AnnotatedContainer\Cli\TerminalOutput;
use Labrador\Http\Bootstrap as HttpBootstrap;
use Monolog\Handler\BufferHandler;
use Monolog\Logger;
use Monolog\Processor\PsrLogMessageProcessor;
use Revolt\EventLoop;
use function Amp\async;
use function Amp\ByteStream\getStderr;
use function Amp\ByteStream\getStdout;
use function Amp\ByteStream\pipe;
use function Amp\trapSignal;
use const SIGILL;
use const SIGINT;

final class WebCommand implements Command {

    public function __construct(
        private readonly string $labradorContainerBootstrap,
    ) {}

    public function getName() : string {
        return 'web';
    }

    public function getHelp() : string {
        return '';
    }

    public function handle(Input $input, TerminalOutput $output) : int {
        if (!is_file($this->labradorContainerBootstrap)) {
            $output->stderr->write('<bold><fg:red>The provided Annotated Container bootstrap is not a valid file!</fg:red></bold>');
            return 1;
        }

        $profiles = $input->getOption('profiles');
        if ($profiles === null) {
            $output->stderr->write(
                '<bold><fg:yellow>NOTICE: No profiles were provided! Using only "default"! <dim>It is recommended that you explicitly pass your desired profiles to this command with the --profiles option.</dim></fg:yellow></bold>'
            );
        }

        $containerBootstrap = include $this->labradorContainerBootstrap;

        if (!is_callable($containerBootstrap)) {
            $output->stderr->write('<bold><fg:red>The Annotated Container bootstrap file MUST return a callable that returns a Cspray\AnnotatedContainer\Bootstrap\Bootstrap instance.</fg:red></bold>');
            return 1;
        }

        $handler = new StreamHandler(getStdout());
        $handler->setFormatter(new ConsoleFormatter());
        $logger = new Logger('labrador.container', [$handler], [new PsrLogMessageProcessor()]);

        $bootstrap = new HttpBootstrap($containerBootstrap($logger));
        $results = $bootstrap->bootstrapApplication();

        $results->application->start();

        $handler->close();

        trapSignal([SIGILL, SIGINT]);

        $results->application->stop();

        return 0;
    }
}