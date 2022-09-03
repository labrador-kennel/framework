<?php

namespace Labrador\Http;

use Amp\Http\Server\ErrorHandler;
use Cspray\AnnotatedContainer\Bootstrap\Bootstrap as AnnotatedContainerBootstrap;

final class Bootstrap {

    public function __construct(
        private readonly AnnotatedContainerBootstrap $bootstrap,
        /**
         * @var list<string> $profiles
         */
        private readonly array $profiles = ['default'],
    ) {}

    public function bootstrapApplication() : BootstrapResults {
        $container = $this->bootstrap->bootstrapContainer(profiles: $this->profiles);

        $app = $container->get(Application::class);
        assert($app instanceof Application);

        return new BootstrapResults($app, $container);
    }

}