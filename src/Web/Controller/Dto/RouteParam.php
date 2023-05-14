<?php

namespace Labrador\Web\Controller\Dto;

use Labrador\Web\Exception\InvalidDtoAttribute;

#[\Attribute(\Attribute::TARGET_PARAMETER)]
class RouteParam implements DtoInjectionAttribute {

    /**
     * @var non-empty-string
     */
    private readonly string $name;

    /**
     * @param string $name
     */
    public function __construct(string $name) {
        $name = trim($name);
        if ($name === '') {
            throw InvalidDtoAttribute::fromRouteParamIsEmpty();
        }
        $this->name = $name;
    }

    /**
     * @return non-empty-string
     */
    public function getName() : string {
        return $this->name;
    }

}