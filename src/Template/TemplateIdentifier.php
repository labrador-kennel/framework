<?php declare(strict_types=1);

namespace Labrador\Template;

interface TemplateIdentifier {

    /**
     * @return non-empty-string
     */
    public function toString() : string;
}
