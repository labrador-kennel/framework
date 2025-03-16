<?php declare(strict_types=1);

namespace Labrador\Test\Unit\Template\Plates;

use Labrador\Template\Exception\BlankTemplateIdentifierProvided;
use Labrador\Template\Plates\PlatesTemplateIdentifier;
use PHPUnit\Framework\TestCase;

final class PlatesTemplateIdentifierTest extends TestCase {

    public function testTemplateStaticConstructorReturnsStringUnchanged() : void {
        self::assertSame(
            'template-name',
            PlatesTemplateIdentifier::template('template-name')->toString()
        );
    }

    public function testTemplateStaticConstructorThrowsExceptionIfEmptyStringProvided() : void {
        $this->expectException(BlankTemplateIdentifierProvided::class);
        $this->expectExceptionMessage('TemplateIdentifiers MUST NOT be blank.');

        PlatesTemplateIdentifier::template('');
    }

    public function testFolderTemplateStaticConstructorReturnsCorrectlyFormattedString() : void {
        self::assertSame(
            'page::template-dir/template-file',
            PlatesTemplateIdentifier::folderTemplate('page', 'template-dir/template-file')->toString()
        );
    }

    public function testFolderTemplateStaticConstructorThrowsExceptionIfEmptyFolderProvided() : void {
        $this->expectException(BlankTemplateIdentifierProvided::class);
        $this->expectExceptionMessage('TemplateIdentifiers MUST NOT be blank.');

        PlatesTemplateIdentifier::folderTemplate('', 'not blank');
    }

    public function testFolderTemplateStaticConstructorThrowsExceptionIfEmptyFileProvided() : void {
        $this->expectException(BlankTemplateIdentifierProvided::class);
        $this->expectExceptionMessage('TemplateIdentifiers MUST NOT be blank.');

        PlatesTemplateIdentifier::folderTemplate('not blank', '');
    }
}
