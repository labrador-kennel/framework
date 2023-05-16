<?php declare(strict_types=1);

namespace Labrador\Security;

use Cspray\AnnotatedContainer\Attribute\Service;

#[Service]
interface TokenGenerator {

    public function generateToken() : string;

}
