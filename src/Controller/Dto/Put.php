<?php

namespace Cspray\Labrador\Http\Controller\Dto;

#[\Attribute(\Attribute::TARGET_METHOD)]
final class Put {

    public function __construct(
        public readonly string $path
    ) {}

}