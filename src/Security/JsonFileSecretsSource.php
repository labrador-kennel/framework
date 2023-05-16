<?php declare(strict_types=1);

namespace Labrador\Security;

use Labrador\Web\Exception\InvalidSecretsSource;

final class JsonFileSecretsSource implements SecretsSource {

    private readonly string $name;
    private readonly array $data;

    public function __construct(string $filePath) {
        if (!file_exists($filePath)) {
            throw InvalidSecretsSource::fromFileNotPresent($filePath);
        }

        [$this->name] = explode('.', basename($filePath));
        $this->data = json_decode(file_get_contents($filePath), associative: true, flags: JSON_THROW_ON_ERROR);
    }

    public function getName() : string {
        return $this->name;
    }

    public function getData() : array {
        return $this->data;
    }
}