<?php

namespace Labrador\Test;

use Cspray\AnnotatedContainer\AnnotatedContainer;
use Cspray\AnnotatedContainer\Bootstrap\Bootstrap as AnnotatedContainerBootstrap;
use Cspray\AnnotatedContainer\Bootstrap\BootstrappingDirectoryResolver;
use Cspray\AnnotatedContainer\ContainerFactory\PhpDiContainerFactory;
use Cspray\AnnotatedContainer\Event\Emitter;
use Cspray\AnnotatedContainer\Profiles;
use Labrador\AsyncEvent\Autowire\RegisterAutowiredListener;
use Labrador\Test\Helper\VfsDirectoryResolver;
use Labrador\Web\Autowire\RegisterControllerListener;

trait BootstrapAwareTestTrait {

    private static function getDefaultConfiguration() : string {
        return <<<XML
<?xml version="1.0" encoding="UTF-8" ?>
<annotatedContainer xmlns="https://annotated-container.cspray.io/schema/annotated-container.xsd" version="dev">
    <scanDirectories>
        <source>
            <dir>src</dir>
            <dir>dummy_app/src</dir>
            <dir>test/Helper</dir>
        </source>
    </scanDirectories>
    <definitionProviders>
        <definitionProvider>Labrador\Web\Autowire\DefinitionProvider</definitionProvider>
        <definitionProvider>Labrador\AsyncEvent\Autowire\DefinitionProvider</definitionProvider>
    </definitionProviders>
</annotatedContainer>
XML;
    }

    private static function getContainer(
        array $profiles,
        BootstrappingDirectoryResolver $directoryResolver = null
    ) : AnnotatedContainer {
        $emitter = new Emitter();
        $emitter->addListener(new RegisterControllerListener());
        $emitter->addListener(new RegisterAutowiredListener());
        $containerBootstrap = AnnotatedContainerBootstrap::fromAnnotatedContainerConventions(
            new PhpDiContainerFactory($emitter),
            $emitter,
            directoryResolver: $directoryResolver ?? new VfsDirectoryResolver()
        );
        return $containerBootstrap->bootstrapContainer(profiles: Profiles::fromList($profiles));
    }
}
