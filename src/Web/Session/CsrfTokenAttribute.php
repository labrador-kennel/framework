<?php declare(strict_types=1);

namespace Labrador\Web\Session;

class CsrfTokenAttribute implements SessionAttribute {

    public function key() : string {
        return 'labrador.csrfToken';
    }
}