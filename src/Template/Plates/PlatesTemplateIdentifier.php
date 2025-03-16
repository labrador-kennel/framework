<?php declare(strict_types=1);

namespace Labrador\Template\Plates;

use Labrador\Template\Exception\BlankTemplateIdentifierProvided;
use Labrador\Template\TemplateIdentifier;

final class PlatesTemplateIdentifier implements TemplateIdentifier {

    /**
     * @param non-empty-string $id
     */
    private function __construct(
        private readonly string $id
    ) {
    }

    public static function template(string $name) : self {
        if ($name === '') {
            throw BlankTemplateIdentifierProvided::fromBlankTemplateIdentifier();
        }
        return new self($name);
    }

    public static function folderTemplate(string $folder, string $name) : self {
        if ($folder === '' || $name === '') {
            throw BlankTemplateIdentifierProvided::fromBlankTemplateIdentifier();
        }
        return new self(sprintf('%s::%s', $folder, $name));
    }


    public function toString() : string {
        return $this->id;
    }
}
