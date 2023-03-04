<?php

namespace Labrador\Http\DependencyInjection;

use Amp\Http\Server\HttpServer;
use Cspray\AnnotatedContainer\StaticAnalysis\DefinitionProvider;
use Cspray\AnnotatedContainer\StaticAnalysis\DefinitionProviderContext;
use Psr\Log\LoggerInterface;
use function Cspray\AnnotatedContainer\service;
use function Cspray\Typiphy\objectType;

class ThirdPartyDefinitionProvider implements DefinitionProvider {

    public function consume(DefinitionProviderContext $context) : void {
        service($context, objectType(HttpServer::class));
        service($context, objectType(LoggerInterface::class));
    }
}