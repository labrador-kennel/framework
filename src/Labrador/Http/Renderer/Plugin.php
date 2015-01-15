<?php

/**
 * 
 * @license See LICENSE in source root
 * @version 1.0
 * @since   1.0
 */

namespace Labrador\Http\Renderer;

use Auryn\Injector;
use Labrador\Plugin\ServiceAwarePlugin;
use Platelets\Renderer;
use Platelets\FileRenderer;
use Platelets\TwoStepRenderer;
use Zend\Escaper\Escaper;

class Plugin implements ServiceAwarePlugin {

    private $templateFilesDir;
    private $layout;

    public function __construct($templateFilesDir, $layout) {
        $this->templateFilesDir = (string) $templateFilesDir;
        $this->layout = (string) $layout;
    }

    public function getName() {
        return 'http_renderer';
    }

    public function boot() {

    }

    public function registerServices(Injector $injector) {
        $fileRenderer = new FileRenderer($this->templateFilesDir);
        $twoStep = new TwoStepRenderer($fileRenderer, $this->layout);
        $helpers = new Helpers($fileRenderer, new Escaper());
        $twoStep->setContext($helpers);
        $injector->share($twoStep);
        $injector->alias(Renderer::class, TwoStepRenderer::class);

    }

} 
