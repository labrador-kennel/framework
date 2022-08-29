<?php

namespace Cspray\Labrador\Http\Test\Unit\Controller\Dto;

use Amp\Http\Server\Driver\Client;
use Amp\Http\Server\Request;
use Cspray\Labrador\Http\Controller\Dto\ValinorDtoFactory;
use Cspray\Labrador\HttpDummyApp\Model\Author;
use League\Uri\Http;
use PHPUnit\Framework\TestCase;

class DtoFactoryTest extends TestCase {

    public function testGetDtoFromJsonEncodedBody() : void {
        $subject = new ValinorDtoFactory();
        $request = new Request(
            $this->getMockBuilder(Client::class)->getMock(),
            'POST',
            Http::createFromString('http://example.com'),
            body: json_encode([
                'name' => 'cspray',
                'email' => 'cspray@example.com',
                'website' => null
            ])
        );
        $author = $subject->create(Author::class, $request);

        self::assertInstanceOf(Author::class, $author);
        self::assertSame('cspray', $author->name);
        self::assertSame('cspray@example.com', $author->email);
        self::assertNull($author->website);
    }

}