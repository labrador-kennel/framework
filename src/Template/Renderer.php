<?php declare(strict_types=1);

namespace Labrador\Template;

use Cspray\AnnotatedContainer\Attribute\Service;

#[Service]
interface Renderer {

    public function render(TemplateIdentifier $templateIdentifier, TemplateData $templateData) : RenderedTemplate;
}
