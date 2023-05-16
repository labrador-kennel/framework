<?php declare(strict_types=1);

namespace Labrador\Security;

final class InMemorySecretsSource implements SecretsSource {

    public function __construct(
        private readonly string $name,
        private readonly array $data
    ) {}

    public function getName() : string {
        return $this->name;
    }

    public function getData() : array {
        return $this->data;
    }
}