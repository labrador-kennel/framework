<?php

/**
 * 
 * @license See LICENSE in source root
 * @version 1.0
 * @since   1.0
 */

namespace Labrador\Http\Renderer;

if (!interface_exists('Platelets\\Renderer')) {
    throw new \RuntimeException('To utilize the ' . __NAMESPACE__ . ' module you must install cspray/platelets');
}

use Platelets\Renderer;
use Zend\Escaper\Escaper;

class Helpers {

    private $renderer;
    private $escaper;

    public function __construct(Renderer $renderer, Escaper $escaper) {
        $this->renderer = $renderer;
        $this->escaper = $escaper;
    }

    public function renderPartial($file, array $locals = []) {
        return $this->renderer->render($file, $locals);
    }

    public function cssTag($href) {
        $format = '<link rel="stylesheet" type="text/css" href="%s" />';
        return sprintf($format, $this->escaper->escapeHtmlAttr($href));
    }

    public function scriptTag($src) {
        $format = '<script src="%s"></script>';
        return sprintf($format, $this->escaper->escapeHtmlAttr($src));
    }

    public function _h($val) {
        return $this->escaper->escapeHtml($val);
    }

    public function _attr($val) {
        return $this->escaper->escapeHtmlAttr($val);
    }

    public function _js($val) {
        return $this->escaper->escapeJs($val);
    }

    public function _css($val) {
        return $this->escaper->escapeCss($val);
    }

    public function _url($val) {
        return $this->escaper->escapeUrl($val);
    }


} 
