<?php

namespace Labrador\Http\Controller\Dto;

#[\Attribute(\Attribute::TARGET_PARAMETER)]
class RouteParam implements DtoInjectionAttribute {

    public function __construct(
        public readonly string $name
    ) {}

}