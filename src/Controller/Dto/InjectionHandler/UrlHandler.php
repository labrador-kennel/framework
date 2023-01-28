<?php

namespace Labrador\Http\Controller\Dto\InjectionHandler;

use Amp\Http\Server\Request;
use Labrador\Http\Controller\Dto\DtoInjectionAttribute;
use Labrador\Http\Controller\Dto\DtoInjectionHandler;
use Labrador\Http\Controller\Dto\Url;
use Psr\Http\Message\UriInterface;
use ReflectionType;

final class UrlHandler implements DtoInjectionHandler {

    public function createDtoValue(Request $request, DtoInjectionAttribute $attribute, ReflectionType $type) : UriInterface {
        return $request->getUri();
    }

    public function isValidType(ReflectionType $type) : bool {
        return $type instanceof \ReflectionNamedType && $type->getName() === UriInterface::class;
    }

    public function getDtoAttributeType() : string {
        return Url::class;
    }
}