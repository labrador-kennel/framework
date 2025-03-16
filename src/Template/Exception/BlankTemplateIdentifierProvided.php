<?php declare(strict_types=1);

namespace Labrador\Template\Exception;

use Labrador\Exception\Exception;

final class BlankTemplateIdentifierProvided extends Exception {

    public static function fromBlankTemplateIdentifier() : self {
        return new self('TemplateIdentifiers MUST NOT be blank.');
    }
}
