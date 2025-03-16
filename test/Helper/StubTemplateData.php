<?php declare(strict_types=1);

namespace Labrador\Test\Helper;

use Labrador\Template\TemplateData;

final class StubTemplateData implements TemplateData {

    public function who() : string {
        return 'kdot';
    }
}
