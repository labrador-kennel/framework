<?php

namespace Labrador\Http\Dto;

use Amp\Http\Server\Request;
use Cspray\AnnotatedContainer\Attribute\Service;
use CuyZ\Valinor\Mapper\Source\Source;
use CuyZ\Valinor\Mapper\TreeMapper;
use CuyZ\Valinor\MapperBuilder;

#[Service]
class ValinorDtoFactory implements DtoFactory {

    private readonly TreeMapper $mapper;

    public function __construct() {
        $this->mapper = (new MapperBuilder())->mapper();
    }

    public function create(string $dtoType, Request $request) : object {
        $object = $this->mapper->map($dtoType, Source::json($request->getBody()->buffer())->camelCaseKeys());
        assert(is_object($object));
        return $object;
    }

}