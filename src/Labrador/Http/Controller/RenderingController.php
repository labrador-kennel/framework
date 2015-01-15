<?php

/**
 * 
 * @license See LICENSE in source root
 * @version 1.0
 * @since   1.0
 */

namespace Labrador\Http\Controller;

use Platelets\Renderer;

abstract class RenderingController extends Controller {

    private $renderer;

    public function __construct(Renderer $renderer) {
        $this->renderer = $renderer;
    }

    protected function render($file) {
        return $this->renderer->render($file, $this->getAll());
    }

} 
