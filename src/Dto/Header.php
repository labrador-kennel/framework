<?php

namespace Labrador\Http\Dto;

#[\Attribute(\Attribute::TARGET_PARAMETER)]
final class Header implements DtoInjectionAttribute {

    public function __construct(
        public readonly string $name
    ) {}

}