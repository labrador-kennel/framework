<?php

namespace Labrador\Http\Controller\Dto\InjectionHandler;

use Amp\Http\Server\Request;
use Labrador\Http\Controller\Dto\DtoInjectionAttribute;
use Labrador\Http\Controller\Dto\DtoInjectionHandler;
use Psr\Http\Message\UriInterface;
use ReflectionType;

final class UrlHandler implements DtoInjectionHandler {

    public function createDtoValue(Request $request, ?DtoInjectionAttribute $attribute, ReflectionType $type) : UriInterface {
        return $request->getUri();
    }

    public function canCreateDtoValue(?DtoInjectionAttribute $attribute, ReflectionType $type) : bool {
        $actualType = $type instanceof \ReflectionNamedType ? $type->getName() : null;
        return $attribute === null && $actualType === UriInterface::class;
    }
}