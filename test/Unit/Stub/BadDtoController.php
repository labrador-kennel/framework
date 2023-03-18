<?php

namespace Labrador\Http\Test\Unit\Stub;

use Labrador\Http\Controller\ControllerActions;
use Labrador\Http\Controller\Dto\Body;
use Labrador\Http\Controller\Dto\Dto;
use Labrador\Http\Controller\Dto\Header;
use Labrador\Http\Controller\Dto\Headers;
use Labrador\Http\Controller\Dto\Method;
use Labrador\Http\Controller\Dto\QueryParams;
use Labrador\Http\Controller\Dto\RouteParam;
use Labrador\Http\Controller\Get;

#[ControllerActions]
final class BadDtoController {

    #[Get('/bad-dto/implicit-headers')]
    public function checkImplicitHeadersDto(#[Headers] $headers) {
        throw new \BadMethodCallException('Not expected to be called');
    }

    #[Get('/bad-dto/int-method')]
    public function checkMethodInt(#[Method] int $method) {
        throw new \BadMethodCallException('Not expected to be called');
    }

    #[Get('/bad-dto/bool-single-header')]
    public function checkSingleHeaderNotArrayOrString(#[Header('Authorization')] bool $token) {
        throw new \BadMethodCallException('Not expected to be called');
    }

    #[Get('/bad-dto/route-param/{foo}')]
    public function checkRouteParamNotUuidOrString(#[RouteParam('foo')] array $foo) {
        throw new \BadMethodCallException('Not expected to be called');
    }

    #[Get('/bad-dto/bool-body')]
    public function checkBodyNotRequestBodyOrString(#[Body] bool $body) {
        throw new \BadMethodCallException('Not expected to be called');
    }

    #[Get('/bad-dto/implicit-dto')]
    public function checkImplicitDto(#[Dto] $widget) {
        throw new \BadMethodCallException('Not expected to be called');
    }

    #[Get('/bad-dto/non-class-dto')]
    public function checkNonClassDto(#[Dto] object $bar) {
        throw new \BadMethodCallException('Not expected to be called');
    }

    #[Get('/bad-dto/check-multiple-attributes')]
    public function checkMultipleAttributes(#[Method] #[Body] string $duo) {
        throw new \BadMethodCallException('Not expected to be called');
    }

}