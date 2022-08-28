<?php

namespace Cspray\Labrador\HttpDummyApp\Controller;

use Cspray\Labrador\Http\Controller\Dto\DtoController;
use Cspray\Labrador\Http\Controller\Dto\Get;
use Cspray\Labrador\Http\Controller\Dto\Headers;

#[DtoController]
final class BadDtoController {

    #[Get('/bad-dto/implicit-headers')]
    public function checkImplicitHeadersDto(#[Headers] $headers) {
        throw new \BadMethodCallException('Not expected to be called');
    }

}