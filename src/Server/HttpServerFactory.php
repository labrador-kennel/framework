<?php

namespace Labrador\Http\Server;

use Amp\Http\Server\HttpServer;
use Amp\Http\Server\SocketHttpServer;
use Cspray\AnnotatedContainer\Attribute\ServiceDelegate;
use Psr\Log\LoggerInterface;

final class HttpServerFactory {

    #[ServiceDelegate]
    public static function createServer(
        HttpServerConfiguration $serverConfiguration,
        LoggerInterface $logger
    ) : HttpServer {
        $socketServer = new SocketHttpServer($logger);

        foreach ($serverConfiguration->getUnencryptedInternetAddresses() as $address) {
            $socketServer->expose($address);
        }

        return $socketServer;
    }

}