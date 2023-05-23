<?php declare(strict_types=1);

namespace Labrador\Cli;

use Amp\Log\ConsoleFormatter;
use Amp\Log\StreamHandler;
use Cspray\AnnotatedContainer\Bootstrap\Bootstrap;
use Cspray\AnnotatedContainer\Cli\Command;
use Cspray\AnnotatedContainer\Cli\Input;
use Cspray\AnnotatedContainer\Cli\TerminalOutput;
use Labrador\Web\Bootstrap as HttpBootstrap;
use Monolog\Logger;
use Monolog\Processor\PsrLogMessageProcessor;
use function Amp\ByteStream\getStdout;
use function Amp\trapSignal;
use const SIGILL;
use const SIGINT;

final class ServeCommand implements Command {

    public function __construct(
    ) {}

    public function getName() : string {
        return 'serve';
    }

    public function getHelp() : string {
        return '';
    }

    public function handle(Input $input, TerminalOutput $output) : int {
        $bootstrap = $input->getOption('bootstrap');
        if (!is_string($bootstrap) || !is_file($bootstrap)) {
            $output->stderr->write('<bold><fg:red>The provided Annotated Container bootstrap is not a valid file!</fg:red></bold>');
            return 1;
        }

        $profiles = $input->getOption('profiles');
        if ($profiles === null) {
            $output->stderr->write(
                '<bold><fg:yellow>NOTICE: No profiles were provided! Using only "default"! <dim>It is recommended that you explicitly pass your desired profiles to this command with the --profiles option.</dim></fg:yellow></bold>'
            );
        }

        $containerBootstrapCallable = include $bootstrap;
        if (!is_callable($containerBootstrapCallable)) {
            $output->stderr->write('<bold><fg:red>The Annotated Container bootstrap file MUST return a callable that returns a Cspray\AnnotatedContainer\Bootstrap\Bootstrap instance.</fg:red></bold>');
            return 1;
        }

        $handler = new StreamHandler(getStdout());
        $handler->setFormatter(new ConsoleFormatter());
        $logger = new Logger('labrador.container', [$handler], [new PsrLogMessageProcessor()]);

        $containerBootstrap = $containerBootstrapCallable($logger);
        if (!$containerBootstrap instanceof Bootstrap) {
            $output->stderr->write('<bold><fg:red>The Annotated Container bootstrap callable MUST return an instance of Cspray\AnnotatedContainer\Bootstrap\Bootstrap.</fg:red></bold>');
            return 1;
        }

        $bootstrap = new HttpBootstrap($containerBootstrap);
        $app = $bootstrap->bootstrapApplication();

        $app->start();

        $handler->close();

        trapSignal([SIGILL, SIGINT]);

        $app->stop();

        return 0;
    }
}