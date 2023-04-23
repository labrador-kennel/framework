<?php

namespace Labrador\Http\Test;

use Cspray\AnnotatedContainer\AnnotatedContainer;
use Cspray\AnnotatedContainer\Bootstrap\Bootstrap as AnnotatedContainerBootstrap;
use Cspray\AnnotatedContainer\Bootstrap\BootstrappingDirectoryResolver;
use Labrador\Http\Bootstrap;
use Labrador\Http\Test\Helper\VfsDirectoryResolver;

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
        <definitionProvider>Labrador\Http\Autowire\DefinitionProvider</definitionProvider>
        <definitionProvider>Labrador\AsyncEvent\Autowire\DefinitionProvider</definitionProvider>
    </definitionProviders>
    <observers>
        <observer>Labrador\Http\Autowire\Observer</observer>
        <observer>Labrador\AsyncEvent\Autowire\Observer</observer>
    </observers>
</annotatedContainer>
XML;
    }

    private static function getContainer(
        array $profiles,
        BootstrappingDirectoryResolver $directoryResolver = null
    ) : AnnotatedContainer {
        $containerBootstrap = new AnnotatedContainerBootstrap($directoryResolver ?? new VfsDirectoryResolver());
        $bootstrap = new Bootstrap($containerBootstrap, profiles: $profiles);
        return $bootstrap->bootstrapApplication()->container;
    }




}