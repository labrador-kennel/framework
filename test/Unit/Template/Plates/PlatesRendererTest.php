<?php declare(strict_types=1);

namespace Labrador\Test\Unit\Template\Plates;

use Labrador\Template\Plates\PlatesEngineDecorator;
use Labrador\Template\Plates\PlatesRenderer;
use Labrador\Template\TemplateData;
use Labrador\Template\TemplateIdentifier;
use Labrador\Test\Helper\StubTemplateData;
use League\Plates\Engine as TemplateEngine;
use PHPUnit\Framework\TestCase;

final class PlatesRendererTest extends TestCase {

    public function testEngineDecoratorSetsTemplateDirectoryAndRendersTemplateFromIt() : void {
        $decorator = new class implements PlatesEngineDecorator {
            public function decorate(TemplateEngine $engine) : void {
                $engine->setDirectory(__DIR__ . '/../../Helper/templates');
            }
        };
        $subject = new PlatesRenderer([$decorator]);

        $identifier = $this->createMock(TemplateIdentifier::class);
        $identifier->expects($this->once())
            ->method('toString')
            ->willReturn('simple-static-page');
        $data = $this->createMock(TemplateData::class);

        $renderedContent = $subject->render($identifier, $data);

        self::assertSame(
            "<p>simple content</p>\n",
            $renderedContent->toString()
        );
    }

    public function testEngineDecoratorSetsTemplateDecoratorAndRendersTemplateWithData() : void {
        $decorator = new class implements PlatesEngineDecorator {
            public function decorate(TemplateEngine $engine) : void {
                $engine->setDirectory(__DIR__ . '/../../Helper/templates');
            }
        };
        $subject = new PlatesRenderer([$decorator]);
        $identifier = $this->createMock(TemplateIdentifier::class);
        $identifier->expects($this->once())
            ->method('toString')
            ->willReturn('data-page');
        $data = new StubTemplateData();

        $actual = $subject->render($identifier, $data);
        $expected = StubTemplateData::class . 'hello kdot';

        self::assertSame($expected, $actual->toString());
    }

    public function testMultipleDecoratorsAreRespectedCheckedByAddingFunctionInSecondDecorator() : void {
        $directoryDecorator = new class implements PlatesEngineDecorator {
            public function decorate(TemplateEngine $engine) : void {
                $engine->setDirectory(__DIR__ . '/../../Helper/templates');
            }
        };
        $functionDecorator = new class implements PlatesEngineDecorator {
            public function decorate(TemplateEngine $engine) : void {
                $engine->registerFunction('vicaVersa', static fn(string $val) => $val . ' == devil');
            }
        };
        $subject = new PlatesRenderer([$directoryDecorator, $functionDecorator]);

        $identifier = $this->createMock(TemplateIdentifier::class);
        $identifier->expects($this->once())
            ->method('toString')
            ->willReturn('page-with-function');
        $data = $this->createMock(TemplateData::class);

        $actual = $subject->render($identifier, $data);
        $expected = 'angel == devil';

        self::assertSame($expected, $actual->toString());
    }

    public function testAllDecoratorsAreOnlyInvokedOnceEvenOnMultipleRenders() : void {
        $decoratorOne = $this->createMock(PlatesEngineDecorator::class);
        $decoratorOne->expects($this->once())
            ->method('decorate')
            ->with($this->isInstanceOf(TemplateEngine::class));
        $decoratorTwo = $this->createMock(PlatesEngineDecorator::class);
        $decoratorTwo->expects($this->once())
            ->method('decorate')
            ->with($this->isInstanceOf(TemplateEngine::class));
        $decoratorThree = $this->createMock(PlatesEngineDecorator::class);
        $decoratorThree->expects($this->once())
            ->method('decorate')
            ->with($this->isInstanceOf(TemplateEngine::class));
        $directoryDecorator = new class implements PlatesEngineDecorator {
            public function decorate(TemplateEngine $engine) : void {
                $engine->setDirectory(__DIR__ . '/../../Helper/templates');
            }
        };
        $subject = new PlatesRenderer([$decoratorOne, $decoratorTwo, $decoratorThree, $directoryDecorator]);

        $identifier = $this->createMock(TemplateIdentifier::class);
        $identifier->expects($this->exactly(3))
            ->method('toString')
            ->willReturn('simple-static-page');

        self::assertSame(
            "<p>simple content</p>\n",
            $subject->render($identifier, $this->createMock(TemplateData::class))->toString()
        );
        self::assertSame(
            "<p>simple content</p>\n",
            $subject->render($identifier, $this->createMock(TemplateData::class))->toString()
        );
        self::assertSame(
            "<p>simple content</p>\n",
            $subject->render($identifier, $this->createMock(TemplateData::class))->toString()
        );
    }

}