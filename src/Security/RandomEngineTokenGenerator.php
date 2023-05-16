<?php declare(strict_types=1);

namespace Labrador\Security;

use Cspray\AnnotatedContainer\Attribute\Service;
use Random\Engine as RandomEngine;

#[Service]
final class RandomEngineTokenGenerator implements TokenGenerator {

    private readonly RandomEngine $random;

    public function __construct(
        RandomEngine $random = null
    ) {
        $this->random = $random ?? new RandomEngine\Secure();
    }


    public function generateToken() : string {
        return bin2hex($this->random->generate());
    }
}