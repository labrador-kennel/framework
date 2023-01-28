<?php

namespace Labrador\Http\Controller\Dto;

use Attribute;

#[Attribute(Attribute::TARGET_PARAMETER)]
final class Body implements DtoInjectionAttribute {

}