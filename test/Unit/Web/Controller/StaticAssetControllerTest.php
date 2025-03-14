<?php declare(strict_types=1);

namespace Labrador\Test\Unit\Web\Controller;

use Amp\Http\HttpStatus;
use Amp\Http\Server\DefaultErrorHandler;
use Amp\Http\Server\Driver\Client;
use Amp\Http\Server\HttpServer;
use Amp\Http\Server\Request;
use Amp\Http\Server\StaticContent\DocumentRoot;
use Labrador\Web\Controller\StaticAssetController;
use League\Uri\Http;
use PHPUnit\Framework\TestCase;

final class StaticAssetControllerTest extends TestCase {

    private Client $client;

    private readonly StaticAssetController $subject;

    private string $assetsDir;

    protected function setUp() : void {
        $this->assetsDir = dirname(__DIR__, 3) . '/Helper/assets';
        $this->client = $this->getMockBuilder(Client::class)->getMock();
        $errorHandler = new DefaultErrorHandler();
        $this->subject = new StaticAssetController(
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
            new Request($this->client, 'GET', Http::createFromString('/not-found-path'))
        );

        self::assertSame(HttpStatus::NOT_FOUND, $response->getStatus());
    }

    public function testPathAttributePresentWithFoundPathReturnsCorrectResponse() : void {
        $request = new Request($this->client, 'GET', Http::createFromString('/assets/main.css'));
        $request->setAttribute('path', 'main.css');
        $response = $this->subject->handleRequest($request);

        self::assertSame(HttpStatus::OK, $response->getStatus());
    }
}
