<?php

namespace Cspray\Labrador\Http\DependencyInjection;

use Amp\Http\Server\ErrorHandler;
use Amp\Http\Server\HttpServer;
use Cspray\AnnotatedContainer\Compile\ContainerDefinitionBuilderContext;
use Cspray\AnnotatedContainer\Compile\ContainerDefinitionBuilderContextConsumer;
use Psr\Log\LoggerInterface;
use function Cspray\AnnotatedContainer\service;
use function Cspray\Typiphy\objectType;

class ThirdPartyServicesProvider implements ContainerDefinitionBuilderContextConsumer {

    public function consume(ContainerDefinitionBuilderContext $context) : void {
        service($context, objectType(HttpServer::class));
        service($context, objectType(LoggerInterface::class));
        service($context, objectType(ErrorHandler::class));
    }
}