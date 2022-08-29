<?php

namespace Cspray\Labrador\Http\Controller\Dto;

use Attribute;

#[Attribute(Attribute::TARGET_METHOD)]
final class Delete {

    public function __construct(
        public readonly string $path
    ) {}

}