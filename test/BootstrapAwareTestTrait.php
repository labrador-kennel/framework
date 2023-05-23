<?php

namespace Labrador\Test;

use Cspray\AnnotatedContainer\AnnotatedContainer;
use Cspray\AnnotatedContainer\Bootstrap\Bootstrap as AnnotatedContainerBootstrap;
use Cspray\AnnotatedContainer\Bootstrap\BootstrappingDirectoryResolver;
use Labrador\Test\Helper\VfsDirectoryResolver;
use Labrador\Web\Bootstrap;
use org\bovigo\vfs\vfsStreamDirectory;

trait BootstrapAwareTestTrait {

    private static function getDefaultConfiguration() : string {
        return <<<XML
<?xml version="1.0" encoding="UTF-8" ?>
<annotatedContainer xmlns="https://annotated-container.cspray.io/schema/annotated-container.xsd">
    <scanDirectories>
        <source>
            <dir>src</dir>
            <dir>dummy_app</dir>
        </source>
    </scanDirectories>
    <definitionProviders>
        <definitionProvider>Labrador\Web\Autowire\DefinitionProvider</definitionProvider>
        <definitionProvider>Labrador\AsyncEvent\Autowire\DefinitionProvider</definitionProvider>
    </definitionProviders>
    <observers>
        <observer>Labrador\Web\Autowire\Observer</observer>
        <observer>Labrador\AsyncEvent\Autowire\Observer</observer>
    </observers>
    <cacheDir>.annotated-container-cache</cacheDir>
</annotatedContainer>
XML;
    }

    private static function getContainer(
        array $profiles,
        BootstrappingDirectoryResolver $directoryResolver = null
    ) : AnnotatedContainer {
        $containerBootstrap = new AnnotatedContainerBootstrap($directoryResolver ?? new VfsDirectoryResolver());
        return $containerBootstrap->bootstrapContainer(profiles: $profiles);
    }

}