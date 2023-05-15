<?php

namespace Labrador\Web\Controller\Dto;

use Attribute;

#[Attribute(Attribute::TARGET_PARAMETER)]
final class Body implements DtoInjectionAttribute {

}