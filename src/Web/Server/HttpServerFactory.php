<?php

namespace Labrador\Web\Server;

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
        HttpServerSettings $serverSettings,
        LoggerInterface $logger,
    ) : HttpServer {
        $socketServer = new SocketHttpServer(
            $logger,
            new ConnectionLimitingServerSocketFactory(
                new LocalSemaphore($serverSettings->totalClientConnectionLimit())
            ),
            new ConnectionLimitingClientFactory(
                new SocketClientFactory($logger),
                $logger,
                $serverSettings->clientConnectionLimitPerIpAddress()
            )
        );

        foreach ($serverSettings->unencryptedInternetAddresses() as $address) {
            $socketServer->expose($address);
        }

        $tlsContext = null;
        if (($tlsCert = $serverSettings->tlsCertificateFile()) !== null) {
            $certificate = new Certificate($tlsCert, $serverSettings->tlsKeyFile());
            $tlsContext = (new BindContext())
                ->withTlsContext((new ServerTlsContext())
                ->withDefaultCertificate($certificate));
        }

        $encryptedAddresses = $serverSettings->encryptedInternetAddresses();
        if (count($encryptedAddresses) > 0 && $tlsContext === null) {
            // TODO throw a more specific exception that https is enabled but no tls context is available
            throw new \RuntimeException();
        }

        // TODO throw an exception if there are encrypted internet addresses specified but no tls file

        foreach ($encryptedAddresses as $encryptedAddress) {
            $socketServer->expose($encryptedAddress, $tlsContext);
        }

        return $socketServer;
    }
}
