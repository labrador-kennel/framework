<?php

namespace Cspray\Labrador\Http\Controller\Dto;

#[\Attribute(\Attribute::TARGET_METHOD)]
final class Get {

    public function __construct(
        public readonly string $path
    ) {}

}