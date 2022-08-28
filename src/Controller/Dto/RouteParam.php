<?php

namespace Cspray\Labrador\Http\Controller\Dto;

#[\Attribute(\Attribute::TARGET_PARAMETER)]
class RouteParam {

    public function __construct(
        public readonly string $name
    ) {}

}