<?php

namespace Labrador\Http\DependencyInjection;

use Amp\Http\Server\HttpServer;
use Cspray\AnnotatedContainer\Compile\ContainerDefinitionBuilderContext;
use Cspray\AnnotatedContainer\Compile\ContainerDefinitionBuilderContextConsumer;
use Cspray\AnnotatedContainer\Compile\DefinitionProvider;
use Cspray\AnnotatedContainer\Compile\DefinitionProviderContext;
use Psr\Log\LoggerInterface;
use function Cspray\AnnotatedContainer\service;
use function Cspray\Typiphy\objectType;

class ThirdPartyServicesProvider implements DefinitionProvider {

    public function consume(DefinitionProviderContext $context) : void {
        service($context, objectType(HttpServer::class));
        service($context, objectType(LoggerInterface::class));
    }
}