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
use Labrador\Logging\LoggerFactory;
use Labrador\Logging\LoggerType;

final class HttpServerFactory {

    #[ServiceDelegate]
    public static function createServer(
        HttpServerSettings $serverSettings,
        LoggerFactory      $loggerFactory
    ) : HttpServer {
        $logger = $loggerFactory->createLogger(LoggerType::WebServer);
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
            $certificate = new Certificate($tlsCert);
            $tlsContext = (new BindContext())
                ->withTlsContext((new ServerTlsContext())
                ->withDefaultCertificate($certificate));
        }

        $encryptedAddresses = $serverSettings->encryptedInternetAddresses();
        if (count($encryptedAddresses) > 0 && $tlsContext === null) {
            throw new \RuntimeException();
        }

        foreach ($encryptedAddresses as $encryptedAddress) {
            $socketServer->expose($encryptedAddress, $tlsContext);
        }

        return $socketServer;
    }
}
