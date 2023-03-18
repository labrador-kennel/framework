<?php

namespace Labrador\Http\Controller\Dto;

use Amp\Http\Server\Request;
use Cspray\AnnotatedContainer\Attribute\Service;
use ReflectionType;

#[Service]
final class DtoInjectionManager {

    /** @var list<DtoInjectionHandler> */
    private array $handlers = [];

    public function addHandler(DtoInjectionHandler $handler) : void {
        $this->handlers[] = $handler;
    }

    public function hasHandlerForAttributeAndType(DtoInjectionAttribute $attribute, ReflectionType $type) : bool {
        foreach ($this->handlers as $handler) {
            if ($handler->canCreateDtoValue($attribute, $type)) {
                return true;
            }
        }

        return false;
    }

    public function hasHandlerForType(ReflectionType $type) : bool {
        foreach ($this->handlers as $handler) {
            if ($handler->canCreateDtoValue(null, $type)) {
                return true;
            }
        }

        return false;
    }

    public function createDtoValue(Request $request, ?DtoInjectionAttribute $attribute, ReflectionType $type) : mixed {
        foreach ($this->handlers as $handler) {
            if ($handler->canCreateDtoValue($attribute, $type)) {
                return $handler->createDtoValue($request, $attribute, $type);
            }
        }

        return null;
    }

}