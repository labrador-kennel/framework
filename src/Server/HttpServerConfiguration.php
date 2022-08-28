<?php

namespace Cspray\Labrador\Http\Server;

use Amp\Socket\InternetAddress;
use Cspray\AnnotatedContainer\Attribute\Service;

#[Service]
interface HttpServerConfiguration {

    /**
     * @return InternetAddress
     */
    public function getUnencryptedInternetAddresses() : array;

    /**
     * @return InternetAddress
     */
    public function getEncryptedInternetAddresses() : array;

    public function getTlsCertificatePath() : ?string;

}