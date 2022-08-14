<?php

namespace Cspray\Labrador\Http\Http;

use Amp\Http\Server\HttpServer;
use Cspray\AnnotatedContainer\Attribute\ServiceDelegate;
use Cspray\Labrador\Http\Configuration\HttpServerConfiguration;
use Psr\Log\LoggerInterface;

final class HttpServerFactory {

    #[ServiceDelegate]
    public static function createServer(
        HttpServerConfiguration $serverConfiguration,
        LoggerInterface $logger
    ) : HttpServer {

    }

}