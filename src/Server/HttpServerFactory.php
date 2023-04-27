<?php

namespace Labrador\Http\Server;

use Amp\Http\Server\Driver\ConnectionLimitingClientFactory;
use Amp\Http\Server\Driver\ConnectionLimitingServerSocketFactory;
use Amp\Http\Server\Driver\SocketClientFactory;
use Amp\Http\Server\HttpServer;
use Amp\Http\Server\SocketHttpServer;
use Amp\Socket\BindContext;
use Amp\Socket\Certificate;
use Amp\Socket\ServerTlsContext;
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
                new LocalSemaphore($serverConfiguration->getTotalClientConnectionLimit())
            ),
            new ConnectionLimitingClientFactory(
                new SocketClientFactory($logger),
                $logger,
                $serverConfiguration->getClientConnectionLimitPerIpAddress()
            )
        );

        foreach ($serverConfiguration->getUnencryptedInternetAddresses() as $address) {
            $socketServer->expose($address);
        }

        $tlsContext = null;
        if (($tlsCert = $serverConfiguration->getTlsCertificateFile()) !== null) {
            $certificate = new Certificate($tlsCert);
            $tlsContext = (new BindContext())
                ->withTlsContext((new ServerTlsContext())
                ->withDefaultCertificate($certificate));
        }

        $encryptedAddresses = $serverConfiguration->getEncryptedInternetAddresses();
        if (count($encryptedAddresses) > 0 && $tlsContext === null) {
            throw new \RuntimeException();
        }

        foreach ($encryptedAddresses as $encryptedAddress) {
            $socketServer->expose($encryptedAddress, $tlsContext);
        }

        return $socketServer;
    }

}