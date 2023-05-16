<?php declare(strict_types=1);

namespace Labrador\Test\Unit\Security;

use Labrador\Security\JsonFileSecretsSource;
use Labrador\Web\Exception\InvalidSecretsSource;
use PHPUnit\Framework\TestCase;
use org\bovigo\vfs\vfsStream as VirtualFilesystem;
use org\bovigo\vfs\vfsStreamDirectory as VirtualDirectory;

final class JsonFileSecretsSourceTest extends TestCase {

    private VirtualDirectory $vfs;

    protected function setUp() : void {
        $this->vfs = VirtualFilesystem::setup();
    }

    public function testPathHasNoFileThrowsException() : void {
        $this->expectException(InvalidSecretsSource::class);
        $this->expectExceptionMessage('Unable to find a secrets file at path vfs://root/not-found');

        new JsonFileSecretsSource('vfs://root/not-found');
    }

    public function testFilePresentButNotValidJsonThrowsError() : void {
        VirtualFilesystem::newFile('secrets.json')->setContent('{invalid json}')->at($this->vfs);

        $this->expectException(\JsonException::class);
        $this->expectExceptionMessage('Syntax error');

        new JsonFileSecretsSource('vfs://root/secrets.json');
    }

    public function testFilePresentWithValidJsonHasCorrectSourceName() : void {
        VirtualFilesystem::newFile('secrets.json')->setContent('{}')->at($this->vfs);

        $subject = new JsonFileSecretsSource('vfs://root/secrets.json');

        self::assertSame('secrets', $subject->getName());
    }

    public function testFilePresentWithValidJsonHasCorrectData() : void {
        VirtualFilesystem::newFile('secrets.json')->setContent('{"foo":"bar"}')->at($this->vfs);

        $subject = new JsonFileSecretsSource('vfs://root/secrets.json');

        self::assertSame(['foo' => 'bar'], $subject->getData());
    }


}