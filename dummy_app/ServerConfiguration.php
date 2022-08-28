<?php

namespace Cspray\Labrador\HttpDummyApp;

use Amp\Socket\InternetAddress;
use Cspray\AnnotatedContainer\Attribute\Inject;
use Cspray\AnnotatedContainer\Attribute\Service;
use Cspray\Labrador\Http\Server\HttpServerConfiguration;

#[Service]
class ServerConfiguration implements HttpServerConfiguration {

    public function __construct(
        #[Inject([
            new InternetAddress('127.0.0.1', 4200)
        ])]
        private readonly array $httpAddresses,
        #[Inject([])]
        private readonly array $httpsAddresses,
        #[Inject(null)]
        private readonly ?string $certificatePath
    ) {}

    public function getUnencryptedInternetAddresses() : array {
        return $this->httpAddresses;
    }

    public function getEncryptedInternetAddresses() : array {
        return $this->httpsAddresses;
    }

    public function getTlsCertificatePath() : ?string {
        return $this->certificatePath;
    }
}