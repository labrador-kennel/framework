<?php

namespace Labrador\Http\Server;

use Amp\Http\Server\Driver\ConnectionLimitingClientFactory;
use Amp\Http\Server\Driver\ConnectionLimitingServerSocketFactory;
use Amp\Http\Server\Driver\SocketClientFactory;
use Amp\Http\Server\HttpServer;
use Amp\Http\Server\SocketHttpServer;
use Amp\Sync\LocalSemaphore;
use Cspray\AnnotatedContainer\Attribute\ServiceDelegate;
use Psr\Log\LoggerInterface;

final class HttpServerFactory {

    #[ServiceDelegate]
    public static function createServer(
        HttpServerConfiguration $serverConfiguration,
        LoggerInterface $logger
    ) : HttpServer {
        $socketServer = new SocketHttpServer(
            $logger,
            new ConnectionLimitingServerSocketFactory(
                new LocalSemaphore(100)
            ),
            new ConnectionLimitingClientFactory(
                new SocketClientFactory($logger),
                $logger
            )
        );

        foreach ($serverConfiguration->getUnencryptedInternetAddresses() as $address) {
            $socketServer->expose($address);
        }

        return $socketServer;
    }

}