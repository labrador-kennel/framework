<?php

namespace Labrador\Web\Controller\Dto;

#[\Attribute(\Attribute::TARGET_PARAMETER)]
final class Header implements DtoInjectionAttribute {

    public function __construct(
        public readonly string $name
    ) {}

}