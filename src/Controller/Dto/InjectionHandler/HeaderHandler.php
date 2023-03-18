<?php

namespace Labrador\Http\Controller\Dto\InjectionHandler;

use Amp\Http\Server\Request;
use Labrador\Http\Controller\Dto\DtoInjectionAttribute;
use Labrador\Http\Controller\Dto\DtoInjectionHandler;
use Labrador\Http\Controller\Dto\Header;
use ReflectionNamedType;
use ReflectionType;

final class HeaderHandler implements DtoInjectionHandler {

    public function createDtoValue(Request $request, ?DtoInjectionAttribute $attribute, ReflectionType $type) : array|string|null {
        $parameterType = $type instanceof ReflectionNamedType ? $type->getName() : null;
        assert($attribute instanceof Header);
        if ($parameterType === 'array') {
            return $request->getHeaderArray($attribute->name);
        }

        return $request->getHeader($attribute->name);
    }

    public function canCreateDtoValue(?DtoInjectionAttribute $attribute, ReflectionType $type) : bool {
        $parameterType = $type instanceof ReflectionNamedType ? $type->getName() : null;
        return $attribute instanceof Header && in_array($parameterType, ['array', 'string']);
    }
}