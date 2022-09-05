<?php

namespace Labrador\Http\Dto;

use Amp\Http\Server\Request;
use Cspray\AnnotatedContainer\Attribute\Service;

#[Service]
interface DtoFactory {

    public function create(string $dtoType, Request $request) : object;

}