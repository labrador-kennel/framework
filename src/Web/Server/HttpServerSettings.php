<?php

namespace Labrador\Web\Server;

use Amp\Socket\InternetAddress;
use Cspray\AnnotatedContainer\Attribute\Service;

#[Service]
interface HttpServerSettings {

    /**
     * @return list<InternetAddress>
     */
    public function getUnencryptedInternetAddresses() : array;

    /**
     * @return list<InternetAddress>
     */
    public function getEncryptedInternetAddresses() : array;

    public function getTlsCertificateFile() : ?string;

    /**
     * @return positive-int
     */
    public function getTotalClientConnectionLimit() : int;

    /**
     * @return positive-int
     */
    public function getClientConnectionLimitPerIpAddress() : int;

}
