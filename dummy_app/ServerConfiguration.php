<?php

namespace Labrador\HttpDummyApp;

use Amp\Socket\InternetAddress;
use Cspray\AnnotatedContainer\Attribute\Inject;
use Cspray\AnnotatedContainer\Attribute\Service;
use Labrador\Http\Server\HttpServerConfiguration;

#[Service]
class ServerConfiguration implements HttpServerConfiguration {

    public function __construct(
        #[Inject([
            new InternetAddress('127.0.0.1', 4200)
        ])]
        private readonly array $httpAddresses,
    ) {}

    public function getUnencryptedInternetAddresses() : array {
        return $this->httpAddresses;
    }

    public function getClientConnectionLimitPerIpAddress() : int {
        return 10;
    }
}