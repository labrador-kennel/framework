<?php

namespace Labrador\Http\Controller\Dto;

use Amp\Http\Server\Request;
use Cspray\AnnotatedContainer\Attribute\Service;
use ReflectionType;

#[Service]
final class DtoInjectionManager {

    /** @var array<string, DtoInjectionHandler> */
    private array $handlers = [];

    public function addHandler(DtoInjectionHandler $handler) : void {
        $this->handlers[$handler->getDtoAttributeType()] = $handler;
    }

    public function isValidTypeForAttribute(DtoInjectionAttribute $attribute, ReflectionType $type) : bool {
        return $this->handlers[$attribute::class]->isValidType($type);
    }

    public function createDtoObject(Request $request, DtoInjectionAttribute $attribute, ReflectionType $type) : mixed {
        return $this->handlers[$attribute::class]->createDtoValue($request, $attribute, $type);
    }

}