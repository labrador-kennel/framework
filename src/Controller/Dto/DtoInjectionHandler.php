<?php

namespace Labrador\Http\Controller\Dto;

use Amp\Http\Server\Request;
use ReflectionType;

interface DtoInjectionHandler {

    public function isValidType(ReflectionType $type) : bool;

    public function createDtoValue(Request $request, DtoInjectionAttribute $attribute, ReflectionType $type) : mixed;

    public function getDtoAttributeType() : string;

}
