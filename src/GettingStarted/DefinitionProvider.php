<?php declare(strict_types=1);

namespace Labrador\Http\GettingStarted;

use Cspray\AnnotatedContainer\StaticAnalysis\DefinitionProvider as AnnotatedContainerDefinitionProvider;
use Cspray\AnnotatedContainer\StaticAnalysis\DefinitionProviderContext;
use Labrador\Http\GettingStarted\Controller\Home;
use Labrador\Http\GettingStarted\Event\Routes;
use Labrador\Http\GettingStarted\Server\Configuration;
use function Cspray\AnnotatedContainer\service;
use function Cspray\Typiphy\objectType;

final class DefinitionProvider implements AnnotatedContainerDefinitionProvider {

    public function consume(DefinitionProviderContext $context) : void {
        service($context, objectType(Home::class));
        service($context, objectType(Routes::class));
        service($context, objectType(Configuration::class));
    }
}