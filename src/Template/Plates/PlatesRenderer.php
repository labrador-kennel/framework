<?php declare(strict_types=1);

namespace Labrador\Template\Plates;

use Cspray\AnnotatedContainer\Attribute\Inject;
use Cspray\AnnotatedContainer\Attribute\Service;
use Cspray\AnnotatedContainer\ContainerFactory\ListOfAsArray;
use Labrador\Template\RenderedTemplate;
use Labrador\Template\Renderer;
use Labrador\Template\TemplateData;
use Labrador\Template\TemplateIdentifier;
use League\Plates\Engine as TemplateEngine;

#[Service]
final class PlatesRenderer implements Renderer {

    private readonly TemplateEngine $engine;

    /**
     * @param list<PlatesEngineDecorator> $decorators
     */
    public function __construct(
        #[Inject(new ListOfAsArray(PlatesEngineDecorator::class))]
        array $decorators
    ) {
        $this->engine = $this->createAndDecorateEngine($decorators);
    }

    public function render(TemplateIdentifier $templateIdentifier, TemplateData $templateData) : RenderedTemplate {
        $content = $this->engine->render($templateIdentifier->toString(), ['data' => $templateData]);
        return new class($content) implements RenderedTemplate {
            public function __construct(
                private readonly string $content
            ) {}

            public function toString() : string {
                return $this->content;
            }
        };
    }

    private function createAndDecorateEngine(array $decorators) : TemplateEngine {
        $engine = new TemplateEngine();
        foreach ($decorators as $decorator) {
            $decorator->decorate($engine);
        }
        return $engine;
    }

}