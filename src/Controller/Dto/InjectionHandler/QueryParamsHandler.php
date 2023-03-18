<?php

namespace Labrador\Http\Controller\Dto\InjectionHandler;

use Amp\Http\Server\Request;
use Labrador\Http\Controller\Dto\DtoInjectionAttribute;
use Labrador\Http\Controller\Dto\DtoInjectionHandler;
use Labrador\Http\Controller\Dto\QueryParams;
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