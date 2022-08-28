<?php

namespace Cspray\Labrador\Http\Controller\Dto;

use Amp\Http\Server\Request;
use Cspray\AnnotatedContainer\Attribute\Service;
use CuyZ\Valinor\Mapper\MappingError;
use CuyZ\Valinor\Mapper\Source\Source;
use CuyZ\Valinor\Mapper\Tree\Message\MessagesFlattener;
use CuyZ\Valinor\Mapper\TreeMapper;
use CuyZ\Valinor\MapperBuilder;

#[Service]
class DtoFactory {

    private readonly TreeMapper $mapper;

    public function __construct() {
        $this->mapper = (new MapperBuilder())->mapper();
    }

    public function create(string $dtoType, Request $request) : object {
        $source = Source::json($request->getBody()->buffer());
        return $this->mapper->map($dtoType, $source->camelCaseKeys());
    }

}