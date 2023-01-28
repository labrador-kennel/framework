<?php

namespace Labrador\Http\Controller\Dto\InjectionHandler;

use Amp\Http\Server\Request;
use Labrador\Http\Controller\Dto\Dto;
use Labrador\Http\Controller\Dto\DtoFactory;
use Labrador\Http\Controller\Dto\DtoInjectionAttribute;
use Labrador\Http\Controller\Dto\DtoInjectionHandler;
use ReflectionType;

final class DtoHandler implements DtoInjectionHandler {

    public function __construct(
        private readonly DtoFactory $factory
    ) {}

    public function createDtoValue(Request $request, DtoInjectionAttribute $attribute, ReflectionType $type) : mixed {
        $parameterType = $type instanceof \ReflectionNamedType ? $type->getName() : null;
        assert($parameterType !== null);
        return $this->factory->create($parameterType, $request);
    }

    public function isValidType(ReflectionType $type) : bool {
        $parameterType = $type instanceof \ReflectionNamedType ? $type->getName() : null;
        return $parameterType !== null && class_exists($parameterType);
    }

    public function getDtoAttributeType() : string {
        return Dto::class;
    }
}