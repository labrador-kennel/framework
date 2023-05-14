<?php

namespace Labrador\Web\Controller\Dto\InjectionHandler;

use Amp\Http\Server\Request;
use Labrador\Controller\Dto\QueryParams;
use Labrador\Web\Controller\Dto\DtoInjectionAttribute;
use Labrador\Web\Controller\Dto\DtoInjectionHandler;
use League\Uri\Components\Query;
use League\Uri\Contracts\QueryInterface;
use ReflectionNamedType;
use ReflectionType;

final class QueryParamsHandler implements DtoInjectionHandler {

    public function createDtoValue(Request $request, ?DtoInjectionAttribute $attribute, ReflectionType $type) : mixed {
        $parameterType = $type instanceof ReflectionNamedType ? $type->getName() : null;
        if (in_array($parameterType, [QueryInterface::class, Query::class], true)) {
            return Query::createFromUri($request->getUri());
        }

        return $request->getUri()->getQuery();
    }

    public function canCreateDtoValue(?DtoInjectionAttribute $attribute, ReflectionType $type) : bool {
        $parameterType = $type instanceof ReflectionNamedType ? $type->getName() : null;

        return $attribute === null && in_array($parameterType, [QueryInterface::class, Query::class], true);
    }
}