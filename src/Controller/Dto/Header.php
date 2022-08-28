<?php

namespace Cspray\Labrador\Http\Controller\Dto;

#[\Attribute(\Attribute::TARGET_PARAMETER)]
final class Header {

    public function __construct(
        public readonly string $name
    ) {}

}