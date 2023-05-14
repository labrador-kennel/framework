<?php declare(strict_types=1);

use Cspray\AnnotatedContainer\Bootstrap\Bootstrap;
use Cspray\AnnotatedContainer\Bootstrap\PreAnalysisObserver;
use Cspray\AnnotatedContainer\Bootstrap\RootDirectoryBootstrappingDirectoryResolver;
use Cspray\AnnotatedContainer\Profiles\ActiveProfiles;
use Psr\Log\LoggerInterface;

return function(LoggerInterface $logger) : Bootstrap {
    $appRoot = dirname(__DIR__, 5);
    $labradorRoot = dirname(__DIR__, 2);

    if (file_exists(sprintf('%s/annotated-container.xml', $appRoot))) {
        $containerRoot = $appRoot;
    } else {
        $containerRoot = $labradorRoot;
    }

    $directoryResolver = new RootDirectoryBootstrappingDirectoryResolver($containerRoot);

    $bootstrap = new Bootstrap($directoryResolver, $logger);
    if ($containerRoot === $labradorRoot) {
        $bootstrap->addObserver(new class($logger) implements PreAnalysisObserver {
            public function __construct(
                private readonly LoggerInterface $logger
            ) {}

            public function notifyPreAnalysis(ActiveProfiles $activeProfiles) : void {
                $this->logger->notice('Did not find an annotated-container.xml configuration in your app\'s root! Falling back to Labrador\'s Getting Started app.');
            }
        });
    }

    return $bootstrap;
};