<?php declare(strict_types=1);

namespace Labrador\Web\Exception;

final class InvalidSecretsSource extends Exception {

    public static function fromFileNotPresent(string $path) : self {
        return new self(sprintf('Unable to find a secrets file at path %s', $path));
    }

}