<?php

namespace Labrador\Http\Controller\Dto\InjectionHandler;

use Amp\Http\Server\Request;
use Amp\Http\Server\RequestBody;
use Labrador\Http\Controller\Dto\Body;
use Labrador\Http\Controller\Dto\DtoInjectionAttribute;
use Labrador\Http\Controller\Dto\DtoInjectionHandler;
use ReflectionType;

final class BodyHandler implements DtoInjectionHandler {

    public function canCreateDtoValue(?DtoInjectionAttribute $attribute, ReflectionType $type) : bool {
        $actualType = $type instanceof \ReflectionNamedType ? $type->getName() : null;
        if ($attribute === null) {
            return $actualType === RequestBody::class;
        }
        return $attribute instanceof Body && in_array($actualType, [RequestBody::class, 'string'], true);
    }

    public function createDtoValue(Request $request, ?DtoInjectionAttribute $attribute, ReflectionType $type) : RequestBody|string {
        $parameterType = $type instanceof \ReflectionNamedType ? $type->getName() : null;
        if ($parameterType === RequestBody::class) {
            return $request->getBody();
        }

        return $request->getBody()->buffer();
    }
}