<?php

namespace Cspray\Labrador\Http\Server;

use Amp\Socket\InternetAddress;
use Cspray\AnnotatedContainer\Attribute\Service;

#[Service]
interface HttpServerConfiguration {

    /**
     * @return list<InternetAddress>
     */
    public function getUnencryptedInternetAddresses() : array;

    /**
     * @return list<InternetAddress>
     */
    public function getEncryptedInternetAddresses() : array;

    public function getTlsCertificatePath() : ?string;

}