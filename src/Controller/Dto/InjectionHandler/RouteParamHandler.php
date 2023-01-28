<?php

namespace Labrador\Http\Controller\Dto\InjectionHandler;

use Amp\Http\Server\Request;
use Labrador\Http\Controller\Dto\DtoInjectionAttribute;
use Labrador\Http\Controller\Dto\DtoInjectionHandler;
use Labrador\Http\Controller\Dto\RouteParam;
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;
use ReflectionType;

final class RouteParamHandler implements DtoInjectionHandler {

    public function createDtoValue(Request $request, DtoInjectionAttribute $attribute, ReflectionType $type) : mixed {
        assert($attribute instanceof RouteParam);
        $parameterType = $type instanceof \ReflectionNamedType ? $type->getName() : null;
        if ($parameterType === UuidInterface::class) {
            return Uuid::fromString((string) $request->getAttribute($attribute->name));
        } else {
            return $request->getAttribute($attribute->name);
        }
    }

    public function isValidType(ReflectionType $type) : bool {
        $parameterType = $type instanceof \ReflectionNamedType ? $type->getName() : null;
        return in_array($parameterType, [UuidInterface::class, 'string']);
    }

    public function getDtoAttributeType() : string {
        return RouteParam::class;
    }
}