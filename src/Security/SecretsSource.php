<?php declare(strict_types=1);

namespace Labrador\Security;

interface SecretsSource {

    public function getName() : string;

    /**
     * @return array<non-empty-string, mixed>
     */
    public function getData() : array;

}