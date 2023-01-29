<?php

namespace Labrador\Http\Controller\Dto\InjectionHandler;

use Amp\Http\Server\Request;
use Amp\Http\Server\RequestBody;
use Labrador\Http\Controller\Dto\Body;
use Labrador\Http\Controller\Dto\DtoInjectionAttribute;
use Labrador\Http\Controller\Dto\DtoInjectionHandler;
use ReflectionType;

final class BodyHandler implements DtoInjectionHandler {

    public function isValidType(ReflectionType $type) : bool {
        $parameterType = $type instanceof \ReflectionNamedType ? $type->getName() : null;
        return in_array($parameterType, [RequestBody::class, 'string']);
    }

    public function createDtoValue(Request $request, DtoInjectionAttribute $attribute, ReflectionType $type) : RequestBody|string {
        $parameterType = $type instanceof \ReflectionNamedType ? $type->getName() : null;
        if ($parameterType === RequestBody::class) {
            return $request->getBody();
        } else {
            return $request->getBody()->buffer();
        }
    }

    public function getDtoAttributeType() : string {
        return Body::class;
    }
}