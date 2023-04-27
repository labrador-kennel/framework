<?php

namespace Labrador\Http\Server;

use Amp\Socket\InternetAddress;
use Cspray\AnnotatedContainer\Attribute\Service;

#[Service]
interface HttpServerConfiguration {

    /**
     * @return list<InternetAddress>
     */
    public function getUnencryptedInternetAddresses() : array;

    public function getEncryptedInternetAddresses() : array;

    public function getTlsCertificateFile() : ?string;

    public function getTotalClientConnectionLimit() : int;

    public function getClientConnectionLimitPerIpAddress() : int;

}
