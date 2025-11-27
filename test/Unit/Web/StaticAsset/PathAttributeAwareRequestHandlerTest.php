<?php declare(strict_types=1);

namespace Labrador\Test\Unit\Web\StaticAsset;

use Amp\Http\HttpStatus;
use Amp\Http\Server\DefaultErrorHandler;
use Amp\Http\Server\Driver\Client;
use Amp\Http\Server\HttpServer;
use Amp\Http\Server\Request;
use Amp\Http\Server\StaticContent\DocumentRoot;
use Labrador\Web\StaticAsset\PathAttributeAwareRequestHandler;
use League\Uri\Http;
use PHPUnit\Framework\TestCase;

final class PathAttributeAwareRequestHandlerTest extends TestCase {
    private Client $client;

    private readonly PathAttributeAwareRequestHandler $subject;

    private string $assetsDir;

    protected function setUp() : void {
        $this->assetsDir = dirname(__DIR__, 3) . '/Helper/assets';
        $this->client = $this->getMockBuilder(Client::class)->getMock();
        $errorHandler = new DefaultErrorHandler();
        $this->subject = new PathAttributeAwareRequestHandler(
            new DocumentRoot(
                $this->getMockBuilder(HttpServer::class)->getMock(),
                $errorHandler,
                $this->assetsDir
            ),
            $errorHandler
        );
    }

    public function testNoPathAttributeReturnsNotFound() : void {
        $response = $this->subject->handleRequest(
            new Request($this->client, 'GET', Http::new('/not-found-path'))
        );

        self::assertSame(HttpStatus::NOT_FOUND, $response->getStatus());
    }

    public function testPathAttributePresentWithFoundPathReturnsCorrectResponse() : void {
        $request = new Request($this->client, 'GET', Http::new('/assets/main.css'));
        $request->setAttribute('path', 'main.css');
        $response = $this->subject->handleRequest($request);

        self::assertSame(HttpStatus::OK, $response->getStatus());
    }
}
