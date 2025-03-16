<?php declare(strict_types=1);

namespace Labrador\Template\Plates;

use Cspray\AnnotatedContainer\Attribute\Service;
use League\Plates\Engine as TemplateEngine;

#[Service]
interface PlatesEngineDecorator {

    public function decorate(TemplateEngine $engine) : void;

}
