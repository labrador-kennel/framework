<?php

namespace Labrador\DummyApp;

use Amp\Socket\InternetAddress;
use Cspray\AnnotatedContainer\Attribute\Inject;
use Cspray\AnnotatedContainer\Attribute\Service;
use Labrador\Web\Server\HttpServerSettings;

#[Service]
class ServerSettings implements HttpServerSettings {

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

    public function getEncryptedInternetAddresses() : array {
        return [];
    }

    public function getTlsCertificateFile() : ?string {
        return null;
    }

    public function getTotalClientConnectionLimit() : int {
        return 1000;
    }
}