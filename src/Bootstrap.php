<?php

namespace Cspray\Labrador\Http;

use Amp\Http\Server\ErrorHandler;
use Cspray\AnnotatedContainer\Bootstrap\Bootstrap as AnnotatedContainerBootstrap;

final class Bootstrap {

    public function __construct(
        private readonly AnnotatedContainerBootstrap $bootstrap,
        /**
         * @var list<string> $profiles
         */
        private readonly array $profiles = ['default'],
        private readonly ?ErrorHandler $errorHandler = null
    ) {}

    public function bootstrapApplication() : BootstrapResults {
        $container = $this->bootstrap->bootstrapContainer(profiles: $this->profiles);

        if ($this->errorHandler instanceof ErrorHandler) {
            $errorHandlerFactory = $container->get(ErrorHandlerFactory::class);
            assert($errorHandlerFactory instanceof ErrorHandlerFactory);
            $errorHandlerFactory->setErrorHandler($this->errorHandler);
        }

        $app = $container->get(Application::class);
        assert($app instanceof Application);

        return new BootstrapResults($app, $container);
    }

}