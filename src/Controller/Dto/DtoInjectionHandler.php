<?php

namespace Labrador\Http\Controller\Dto;

use Amp\Http\Server\Request;
use ReflectionType;

interface DtoInjectionHandler {

    public function canCreateDtoValue(?DtoInjectionAttribute $attribute, ReflectionType $type) : bool;

    public function createDtoValue(Request $request, ?DtoInjectionAttribute $attribute, ReflectionType $type) : mixed;

}
