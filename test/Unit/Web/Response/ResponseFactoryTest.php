<?php declare(strict_types=1);

namespace Labrador\Test\Unit\Web\Response;

use Amp\Http\HttpStatus;
use Amp\Http\Server\ErrorHandler;
use Amp\Http\Server\Response;
use Labrador\Template\RenderedTemplate;
use Labrador\Web\Response\Exception\AmbiguousRedirectLocation;
use Labrador\Web\Response\ResponseFactory;
use League\Uri\Http;
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

    public function testSeeOtherResponseReturnsCorrectStatusAndLocationHeader() : void {
        $response = $this->subject->seeOther(
            Http::new('https://example.com/see/other')
        );

        self::assertSame(
            HttpStatus::SEE_OTHER,
            $response->getStatus()
        );
        self::assertSame(
            'https://example.com/see/other',
            $response->getHeader('Location')
        );
    }

    public function testSeeOtherHasLocationInHeadersThrowsException() : void {
        $this->expectException(AmbiguousRedirectLocation::class);
        $this->expectExceptionMessage(
            'Attempted to create a 303 Response but multiple location targets were provided. Do not include a' .
            ' Location header when creating this type of response.'
        );

        $this->subject->seeOther(
            Http::new('http://example.com'),
            ['Location' => 'http://example.com/something/else']
        );
    }

    public function testSeeOtherHasLocationInHeadersCheckIsCaseInsensitiveThrowsException() : void {
        $this->expectException(AmbiguousRedirectLocation::class);
        $this->expectExceptionMessage(
            'Attempted to create a 303 Response but multiple location targets were provided. Do not include a' .
            ' Location header when creating this type of response.'
        );

        $this->subject->seeOther(
            Http::new('http://example.com'),
            ['location' => 'http://example.com/something/else']
        );
    }

    public function testSeeOtherHasCorrectCustomHeaders() : void {
        $response = $this->subject->seeOther(
            Http::new('http://sub.example.com'),
            [
                'Custom-Header' => 'my-custom-val',
                'Powered-By' => 'Labrador'
            ]
        );

        self::assertSame(HttpStatus::SEE_OTHER, $response->getStatus());
        self::assertSame(
            [
                'location' => ['http://sub.example.com'],
                'custom-header' => ['my-custom-val'],
                'powered-by' => ['Labrador']
            ],
            $response->getHeaders()
        );
    }
}
