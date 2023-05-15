<?php

namespace Labrador\Web\Controller\Dto\InjectionHandler;

use Amp\Http\Server\Request;
use Labrador\Web\Controller\Dto\DtoInjectionAttribute;
use Labrador\Web\Controller\Dto\DtoInjectionHandler;
use Labrador\Web\Controller\Dto\Headers;
use ReflectionNamedType;
use ReflectionType;

final class HeadersHandler implements DtoInjectionHandler {

    public function createDtoValue(Request $request, ?DtoInjectionAttribute $attribute, ReflectionType $type) : array {
        return $request->getHeaders();
    }

    public function canCreateDtoValue(?DtoInjectionAttribute $attribute, ReflectionType $type) : bool {
        $parameterType = $type instanceof ReflectionNamedType ? $type->getName() : null;
        return $attribute instanceof Headers && $parameterType === 'array';
    }
}