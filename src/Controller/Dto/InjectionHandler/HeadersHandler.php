<?php

namespace Labrador\Http\Controller\Dto\InjectionHandler;

use Amp\Http\Server\Request;
use Labrador\Http\Controller\Dto\DtoInjectionAttribute;
use Labrador\Http\Controller\Dto\DtoInjectionHandler;
use Labrador\Http\Controller\Dto\Headers;
use ReflectionNamedType;
use ReflectionType;

final class HeadersHandler implements DtoInjectionHandler {

    public function createDtoValue(Request $request, DtoInjectionAttribute $attribute, ReflectionType $type) : array {
        return $request->getHeaders();
    }

    public function isValidType(ReflectionType $type) : bool {
        $parameterType = $type instanceof ReflectionNamedType ? $type->getName() : null;
        return $parameterType === 'array';
    }

    public function getDtoAttributeType() : string {
        return Headers::class;
    }
}