<?php

/**
 * 
 * @license See LICENSE in source root
 * @version 1.0
 * @since   1.0
 */

namespace Labrador\Test\Unit\Renderer;

use Labrador\Http\Renderer\Helpers;
use Platelets\Renderer;
use PHPUnit_Framework_TestCase as UnitTestCase;
use Zend\Escaper\Escaper;

class HelpersTest extends UnitTestCase {

    private $mockRenderer;
    private $mockEscaper;

    public function setUp() {
        $this->mockRenderer = $this->getMock(Renderer::class);
        $this->mockEscaper = $this->getMockBuilder(Escaper::class)->disableOriginalConstructor()->getMock();
    }

    private function getHelper() {
        return new Helpers($this->mockRenderer, $this->mockEscaper);
    }

    public function testCssTag() {
        $this->mockEscaper->expects($this->once())->method('escapeHtmlAttr')->with('foo.css')->willReturn('clean-foo.css');
        $expected = '<link rel="stylesheet" type="text/css" href="clean-foo.css" />';
        $this->assertSame($expected, $this->getHelper()->cssTag('foo.css'));
    }

    public function testScriptTag() {
        $this->mockEscaper->expects($this->once())->method('escapeHtmlAttr')->with('bar.js')->willReturn('clean-bar.js');
        $expected = '<script src="clean-bar.js"></script>';
        $this->assertSame($expected, $this->getHelper()->scriptTag('bar.js'));
    }

    public function escapeDataProvider() {
        return [
            ['escapeHtml', '_h'],
            ['escapeHtmlAttr', '_attr'],
            ['escapeJs', '_js'],
            ['escapeCss', '_css'],
            ['escapeUrl', '_url']
        ];
    }

    /**
     * @dataProvider escapeDataProvider
     */
    public function testEscapeData($escaperMethod, $helperMethod) {
        $this->mockEscaper->expects($this->once())->method($escaperMethod)->with('some value')->willReturn('cleaned');
        $this->assertSame('cleaned', $this->getHelper()->$helperMethod('some value'));
    }

} 
