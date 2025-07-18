<?php declare(strict_types=1);

namespace Labrador\Web\Session;

interface SessionAttribute {

    /**
     * @return non-empty-string
     */
    public function key() : string;

}