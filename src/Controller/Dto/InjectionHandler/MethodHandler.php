<?php

namespace Labrador\Http\Controller\Dto\InjectionHandler;

use Amp\Http\Server\Request;
use Labrador\Http\Controller\Dto\DtoInjectionAttribute;
use Labrador\Http\Controller\Dto\DtoInjectionHandler;
use Labrador\Http\Controller\Dto\Method;
use ReflectionNamedType;
use ReflectionType;

final class MethodHandler implements DtoInjectionHandler {

    public function createDtoValue(Request $request, ?DtoInjectionAttribute $attribute, ReflectionType $type) : string {
        return $request->getMethod();
    }

    public function canCreateDtoValue(?DtoInjectionAttribute $attribute, ReflectionType $type) : bool {
        return $attribute instanceof Method && $type instanceof ReflectionNamedType && $type->getName() === 'string';
    }
}