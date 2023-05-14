<?php

namespace Labrador\Web\Controller\Dto\InjectionHandler;

use Amp\Http\Server\Request;
use Labrador\Web\Controller\Dto\DtoInjectionAttribute;
use Labrador\Web\Controller\Dto\DtoInjectionHandler;
use Labrador\Web\Controller\Dto\RouteParam;
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;
use ReflectionType;

final class RouteParamHandler implements DtoInjectionHandler {

    public function createDtoValue(Request $request, ?DtoInjectionAttribute $attribute, ReflectionType $type) : mixed {
        assert($attribute instanceof RouteParam);
        $parameterType = $type instanceof \ReflectionNamedType ? $type->getName() : null;
        if ($parameterType === UuidInterface::class) {
            return Uuid::fromString((string) $request->getAttribute($attribute->getName()));
        } else {
            return $request->getAttribute($attribute->getName());
        }
    }

    public function canCreateDtoValue(?DtoInjectionAttribute $attribute, ReflectionType $type) : bool {
        $parameterType = $type instanceof \ReflectionNamedType ? $type->getName() : null;
        return $attribute instanceof RouteParam && in_array($parameterType, [UuidInterface::class, 'string']);
    }

}