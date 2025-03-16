<?php declare(strict_types=1);

namespace Labrador\Test\Unit\Web\Response;

use Amp\Http\HttpStatus;
use Amp\Http\Server\ErrorHandler;
use Amp\Http\Server\Response;
use Labrador\Template\RenderedTemplate;
use Labrador\Web\Response\ResponseFactory;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

final class ResponseFactoryTest extends TestCase {

    private ResponseFactory $subject;
    private ErrorHandler&MockObject $errorHandler;

    protected function setUp() : void {
        $this->errorHandler = $this->createMock(ErrorHandler::class);
        $this->subject = new ResponseFactory($this->errorHandler);
    }

    public function testHtmlResponseHasCorrectBodyContentWhenString() : void {
        $response = $this->subject->html('<p>my html content</p>');

        self::assertSame(
            '<p>my html content</p>',
            $response->getBody()->read()
        );
    }

    public function testHtmlResponseHasCorrectBodyContentWhenRenderedTemplate() : void {
        $template = $this->createMock(RenderedTemplate::class);
        $template->expects($this->once())
            ->method('toString')
            ->willReturn('<p>my rendered content</p>');
        $response = $this->subject->html($template);

        self::assertSame(
            '<p>my rendered content</p>',
            $response->getBody()->read()
        );
    }

    public function testHtmlResponseHasCorrectContentTypeHeaderByDefault() : void {
        $response = $this->subject->html('my content');

        self::assertSame('text/html; charset=utf-8', $response->getHeader('Content-Type'));
    }

    public function testHtmlResponseRespectsCustomHeadersPassedIn() : void {
        $response = $this->subject->html('something else', [
            'My-Custom-Header' => 'my-custom-value'
        ]);

        self::assertSame(
            'my-custom-value',
            $response->getHeader('My-Custom-Header')
        );
    }

    public function testHtmlResponseAllowsCustomizingContentTypeInCustomHeaders() : void {
        $response = $this->subject->html('why would you do this?', [
            'Content-Type' => 'my-new-content-type'
        ]);

        self::assertSame(
            'my-new-content-type',
            $response->getHeader('Content-Type')
        );
    }

    public function testHtmlResponseSendsOkByDefault() : void {
        $response = $this->subject->html('check status code');

        self::assertSame(
            HttpStatus::OK,
            $response->getStatus()
        );
    }

    public function testHtmlResponseRespectsStatusCodePassed() : void {
        $response = $this->subject->html('custom status code', status: HttpStatus::FOUND);

        self::assertSame(
            HttpStatus::FOUND,
            $response->getStatus()
        );
    }

    public function testErrorResponseReturnsWhateverIsGeneratedByErrorHandler() : void {
        $this->errorHandler->expects($this->once())
            ->method('handleError')
            ->with(HttpStatus::FORBIDDEN)
            ->willReturn($response = new Response());

        self::assertSame($response, $this->subject->error(HttpStatus::FORBIDDEN));
    }
}
