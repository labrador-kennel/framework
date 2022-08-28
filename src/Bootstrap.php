<?php

namespace Cspray\Labrador\Http;

use Amp\Http\Server\ErrorHandler;
use Cspray\AnnotatedContainer\Bootstrap\Bootstrap as AnnotatedContainerBootstrap;

final class Bootstrap {

    public function __construct(
        private readonly AnnotatedContainerBootstrap $bootstrap,
        private readonly array $profiles = ['default'],
        private readonly ?ErrorHandler $errorHandler = null
    ) {}

    public function bootstrapApplication() : BootstrapResults {
        $container = $this->bootstrap->bootstrapContainer(profiles: $this->profiles);

        if ($this->errorHandler instanceof ErrorHandler) {
            $container->get(ErrorHandlerFactory::class)->setErrorHandler($this->errorHandler);
        }

        $app = $container->get(Application::class);

        return new BootstrapResults($app, $container);
    }

}