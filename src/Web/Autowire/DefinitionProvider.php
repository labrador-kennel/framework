<?php

namespace Labrador\Web\Autowire;

use Amp\Http\Server\ErrorHandler;
use Amp\Http\Server\HttpServer;
use Cspray\AnnotatedContainer\StaticAnalysis\DefinitionProvider as AnnotatedContainerDefinitionProvider;
use Cspray\AnnotatedContainer\StaticAnalysis\DefinitionProviderContext;
use Psr\Clock\ClockInterface;
use Psr\Log\LoggerInterface;
use function Cspray\AnnotatedContainer\Definition\service;
use function Cspray\AnnotatedContainer\Reflection\types;

final class DefinitionProvider implements AnnotatedContainerDefinitionProvider {

    public function consume(DefinitionProviderContext $context) : void {
        $context->addServiceDefinition(service(types()->class(HttpServer::class)));
        $context->addServiceDefinition(service(types()->class(ErrorHandler::class)));
        $context->addServiceDefinition(service(types()->class(LoggerInterface::class)));
        $context->addServiceDefinition(service(types()->class(ClockInterface::class)));
    }
}
